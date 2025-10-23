<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Generates a unique booking reference.
 */
function generate_booking_reference(PDO $pdo): string
{
    do {
        $reference = 'DC' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT id FROM bookings WHERE reference = :ref');
        $stmt->execute([':ref' => $reference]);
    } while ($stmt->fetchColumn() !== false);

    return $reference;
}

/**
 * Calculates nights between arrival and departure.
 */
function calculate_nights(string $arrival, string $departure): int
{
    $start = new DateTimeImmutable($arrival);
    $end = new DateTimeImmutable($departure);
    $diff = $start->diff($end);

    return max(0, (int) $diff->days);
}

/**
 * Calculates total price using property base rate. Can be extended with seasonal pricing.
 *
 * @return array{nights:int,total:float,currency:string,nightly_rate:float}
 */
function calculate_booking_total(PDO $pdo, int $propertyId, string $arrival, string $departure): array
{
    $stmt = $pdo->prepare('SELECT base_rate FROM properties WHERE id = :id');
    $stmt->execute([':id' => $propertyId]);
    $baseRate = (float) ($stmt->fetchColumn() ?: 0.0);

    $nights = calculate_nights($arrival, $departure);
    $nightly = $baseRate > 0 ? $baseRate : 0.0;
    $total = round($nightly * $nights, 2);

    return [
        'nights' => $nights,
        'total' => $total,
        'currency' => 'EUR',
        'nightly_rate' => $nightly,
    ];
}

/**
 * Checks whether a date range is free for a property.
 */
function is_property_range_available(PDO $pdo, int $propertyId, string $arrival, string $departure, ?int $ignoreBookingId = null): bool
{
    $query = <<<SQL
        SELECT id FROM calendar_blocks
        WHERE property_id = :property
          AND start_date < :departure
          AND end_date > :arrival
          AND source IN ('internal_booking','external_ics','manual_block','pending')
    SQL;

    $params = [
        ':property' => $propertyId,
        ':arrival' => $arrival,
        ':departure' => $departure,
    ];

    if ($ignoreBookingId !== null) {
        $query .= ' AND (booking_id IS NULL OR booking_id != :ignore_booking)';
        $params[':ignore_booking'] = $ignoreBookingId;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchColumn() === false;
}

/**
 * Creates or updates a calendar block.
 */
function upsert_calendar_block(PDO $pdo, array $data): int
{
    $fields = [
        'property_id' => $data['property_id'],
        'booking_id' => $data['booking_id'] ?? null,
        'source' => $data['source'] ?? 'pending',
        'external_uid' => $data['external_uid'] ?? null,
        'title' => $data['title'] ?? null,
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
    ];

    if (!empty($data['id'])) {
        $stmt = $pdo->prepare(
            'UPDATE calendar_blocks SET source = :source, title = :title, start_date = :start, end_date = :end,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':source' => $fields['source'],
            ':title' => $fields['title'],
            ':start' => $fields['start_date'],
            ':end' => $fields['end_date'],
            ':id' => $data['id'],
        ]);
        return (int) $data['id'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO calendar_blocks (property_id, booking_id, source, external_uid, title, start_date, end_date)
         VALUES (:property, :booking, :source, :external_uid, :title, :start_date, :end_date)'
    );
    $stmt->execute([
        ':property' => $fields['property_id'],
        ':booking' => $fields['booking_id'],
        ':source' => $fields['source'],
        ':external_uid' => $fields['external_uid'],
        ':title' => $fields['title'],
        ':start_date' => $fields['start_date'],
        ':end_date' => $fields['end_date'],
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Creates a pending booking record with associated contact & travellers.
 *
 * @param array<string,mixed> $payload
 * @param array<int, array<string,mixed>> $travellers
 * @return array{booking_id:int,reference:string}
 */
function create_pending_booking(PDO $pdo, array $payload, array $travellers): array
{
    $pdo->beginTransaction();

    try {
        $total = calculate_booking_total($pdo, $payload['property_id'], $payload['arrival_date'], $payload['departure_date']);
        if ($total['nights'] <= 0) {
            throw new RuntimeException('Invalid stay length.');
        }
        $reference = generate_booking_reference($pdo);

        $stmt = $pdo->prepare(
            'INSERT INTO bookings
                (property_id, reference, status, arrival_date, departure_date, nights, adults, children, infants,
                 total_amount, currency, email, email_token)
             VALUES
                (:property, :reference, :status, :arrival, :departure, :nights, :adults, :children, :infants,
                 :total, :currency, :email, :token)'
        );
        $stmt->execute([
            ':property'  => $payload['property_id'],
            ':reference' => $reference,
            ':status'    => 'awaiting_email',
            ':arrival'   => $payload['arrival_date'],
            ':departure' => $payload['departure_date'],
            ':nights'    => $total['nights'],
            ':adults'    => $payload['adults'],
            ':children'  => $payload['children'],
            ':infants'   => $payload['infants'],
            ':total'     => $total['total'],
            ':currency'  => $total['currency'],
            ':email'     => $payload['email'],
            ':token'     => $payload['email_token'],
        ]);

        $bookingId = (int) $pdo->lastInsertId();

        $stmtContact = $pdo->prepare(
            'INSERT INTO booking_contacts
                (booking_id, full_name, address_line, city, region, postal_code, country, phone)
             VALUES (:booking, :name, :address, :city, :region, :postal, :country, :phone)'
        );
        $stmtContact->execute([
            ':booking' => $bookingId,
            ':name'    => $payload['full_name'],
            ':address' => $payload['address'],
            ':city'    => $payload['city'],
            ':region'  => $payload['region'],
            ':postal'  => $payload['postal_code'],
            ':country' => $payload['country'],
            ':phone'   => $payload['phone'],
        ]);

        if ($travellers) {
            $stmtTraveller = $pdo->prepare(
                'INSERT INTO booking_travellers (booking_id, traveller_type, age)
                 VALUES (:booking, :type, :age)'
            );
            foreach ($travellers as $traveller) {
                $stmtTraveller->execute([
                    ':booking' => $bookingId,
                    ':type'    => $traveller['type'],
                    ':age'     => $traveller['age'],
                ]);
            }
        }

        upsert_calendar_block($pdo, [
            'property_id' => $payload['property_id'],
            'booking_id'  => $bookingId,
            'source'      => 'pending',
            'title'       => 'Pending booking ' . $reference,
            'start_date'  => $payload['arrival_date'],
            'end_date'    => $payload['departure_date'],
        ]);

        record_booking_event($pdo, $bookingId, 'guest', null, 'created', [
            'reference' => $reference,
            'email' => $payload['email'],
        ]);

        $pdo->commit();

        return [
            'booking_id' => $bookingId,
            'reference'  => $reference,
        ];
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

/**
 * Adds an entry to booking events.
 *
 * @param array<string,mixed>|null $details
 */
function record_booking_event(PDO $pdo, int $bookingId, string $actorType, ?string $actor, string $eventType, ?array $details = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO booking_events (booking_id, actor_type, actor_identifier, event_type, details)
         VALUES (:booking, :actor_type, :actor_identifier, :event_type, :details)'
    );
    $stmt->execute([
        ':booking'          => $bookingId,
        ':actor_type'       => $actorType,
        ':actor_identifier' => $actor,
        ':event_type'       => $eventType,
        ':details'          => $details ? json_encode($details, JSON_THROW_ON_ERROR) : null,
    ]);
}

/**
 * Fetches bookings for admin overview.
 *
 * @return array<int, array<string,mixed>>
 */
function fetch_bookings(PDO $pdo, ?int $propertyId = null, ?string $query = null): array
{
    $sql = <<<SQL
        SELECT b.*, p.name AS property_name
        FROM bookings b
        JOIN properties p ON p.id = b.property_id
    SQL;

    $conditions = [];
    $params = [];

    if ($propertyId !== null) {
        $conditions[] = 'b.property_id = :property';
        $params[':property'] = $propertyId;
    }

    if ($query !== null && $query !== '') {
        $conditions[] = '(b.reference LIKE :query OR p.name LIKE :query)';
        $params[':query'] = '%' . $query . '%';
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY b.arrival_date DESC, b.id DESC LIMIT 200';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Fetches booking detail with relations.
 *
 * @return array<string,mixed>|null
 */
function fetch_booking(PDO $pdo, int $bookingId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, p.name AS property_name, p.slug AS property_slug
         FROM bookings b
         JOIN properties p ON p.id = b.property_id
         WHERE b.id = :id'
    );
    $stmt->execute([':id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) {
        return null;
    }

    $stmtContact = $pdo->prepare('SELECT * FROM booking_contacts WHERE booking_id = :id LIMIT 1');
    $stmtContact->execute([':id' => $bookingId]);
    $booking['contact'] = $stmtContact->fetch(PDO::FETCH_ASSOC);

    $stmtTravellers = $pdo->prepare('SELECT * FROM booking_travellers WHERE booking_id = :id');
    $stmtTravellers->execute([':id' => $bookingId]);
    $booking['travellers'] = $stmtTravellers->fetchAll(PDO::FETCH_ASSOC);

    $stmtEvents = $pdo->prepare('SELECT * FROM booking_events WHERE booking_id = :id ORDER BY created_at DESC');
    $stmtEvents->execute([':id' => $bookingId]);
    $booking['events'] = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

    return $booking;
}

/**
 * Updates booking status and related calendar block.
 */
function update_booking_status(PDO $pdo, int $bookingId, string $status, string $actorType = 'system', ?string $actor = null, ?array $details = null): void
{
    $stmt = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id');
    $stmt->execute([':status' => $status, ':id' => $bookingId]);

    if ($status === 'cancelled' || $status === 'expired') {
        $stmtDelete = $pdo->prepare(
            "DELETE FROM calendar_blocks WHERE booking_id = :booking AND source IN ('pending','internal_booking')"
        );
        $stmtDelete->execute([':booking' => $bookingId]);
    } elseif ($status === 'confirmed') {
        $stmtUpdate = $pdo->prepare(
            "UPDATE calendar_blocks SET source = 'internal_booking', title = :title
             WHERE booking_id = :booking"
        );
        $stmtUpdate->execute([
            ':title' => 'Confirmed booking #' . $bookingId,
            ':booking' => $bookingId,
        ]);
    }

    record_booking_event($pdo, $bookingId, $actorType, $actor, 'status_change', [
        'status' => $status,
        'details' => $details,
    ]);
}

/**
 * Lists calendar blocks for a property and month view.
 *
 * @return array<int, array<string,mixed>>
 */
function fetch_calendar_blocks(PDO $pdo, int $propertyId, string $start, string $end): array
{
    $stmt = $pdo->prepare(
        'SELECT cb.*, b.reference
         FROM calendar_blocks cb
         LEFT JOIN bookings b ON b.id = cb.booking_id
         WHERE cb.property_id = :property
           AND cb.start_date < :end
           AND cb.end_date > :start
         ORDER BY cb.start_date ASC'
    );
    $stmt->execute([
        ':property' => $propertyId,
        ':start' => $start,
        ':end' => $end,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Ensures a property has a calendar export token.
 */
function ensure_property_calendar(PDO $pdo, int $propertyId): array
{
    $stmt = $pdo->prepare('SELECT * FROM property_calendars WHERE property_id = :property');
    $stmt->execute([':property' => $propertyId]);
    $calendar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($calendar) {
        return $calendar;
    }

    $token = bin2hex(random_bytes(20));
    $stmtInsert = $pdo->prepare(
        'INSERT INTO property_calendars (property_id, export_token)
         VALUES (:property, :token)'
    );
    $stmtInsert->execute([
        ':property' => $propertyId,
        ':token' => $token,
    ]);

    $stmt->execute([':property' => $propertyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Converts calendar blocks into iCalendar format.
 */
function render_property_ics(PDO $pdo, int $propertyId): string
{
    $calendar = ensure_property_calendar($pdo, $propertyId);

    $stmt = $pdo->prepare(
        'SELECT cb.*, b.reference, p.name AS property_name
         FROM calendar_blocks cb
         JOIN properties p ON p.id = cb.property_id
         LEFT JOIN bookings b ON b.id = cb.booking_id
         WHERE cb.property_id = :property
           AND cb.source IN (\'internal_booking\', \'manual_block\', \'external_ics\')'
    );
    $stmt->execute([':property' => $propertyId]);
    $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Dubrovnik Coast//Availability//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
    ];

    foreach ($blocks as $block) {
        $uid = $block['external_uid'] ?: ($block['booking_id'] ? 'booking-' . $block['booking_id'] : 'block-' . $block['id']);
        $start = (new DateTimeImmutable($block['start_date']))->format('Ymd');
        $end = (new DateTimeImmutable($block['end_date']))->format('Ymd');
        $summary = $block['title'] ?: (($block['reference'] ?? '') !== '' ? 'Booking ' . $block['reference'] : 'Unavailable');

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid . '@dubrovnik-coast.com';
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART;VALUE=DATE:' . $start;
        $lines[] = 'DTEND;VALUE=DATE:' . $end;
        $lines[] = 'SUMMARY:' . addcslashes($summary, ',;');
        $lines[] = 'END:VEVENT';
    }

    $lines[] = 'END:VCALENDAR';

    return implode("\r\n", $lines) . "\r\n";
}

/**
 * Updates feed URLs for a property calendar.
 *
 * @param array{airbnb?:string, booking?:string, custom?:string} $feeds
 */
function update_property_calendar_feeds(PDO $pdo, int $propertyId, array $feeds): void
{
    $calendar = ensure_property_calendar($pdo, $propertyId);
    $stmt = $pdo->prepare(
        'UPDATE property_calendars
         SET airbnb_feed_url = :airbnb,
             booking_feed_url = :booking,
             custom_feed_url = :custom,
             updated_at = CURRENT_TIMESTAMP
         WHERE id = :id'
    );
    $stmt->execute([
        ':airbnb' => $feeds['airbnb'] !== '' ? $feeds['airbnb'] : null,
        ':booking' => $feeds['booking'] !== '' ? $feeds['booking'] : null,
        ':custom' => $feeds['custom'] !== '' ? $feeds['custom'] : null,
        ':id' => $calendar['id'],
    ]);
}

/**
 * Imports events from external ICS feeds into calendar blocks.
 *
 * @return array{imported:int, feeds:int}
 */
function sync_property_calendar_from_feeds(PDO $pdo, int $propertyId): array
{
    $calendar = ensure_property_calendar($pdo, $propertyId);
    $feeds = array_filter([
        'airbnb' => $calendar['airbnb_feed_url'] ?? '',
        'booking' => $calendar['booking_feed_url'] ?? '',
        'custom' => $calendar['custom_feed_url'] ?? '',
    ]);

    if (!$feeds) {
        return ['imported' => 0, 'feeds' => 0];
    }

    $pdo->prepare('DELETE FROM calendar_blocks WHERE property_id = :property AND source = \'external_ics\'')
        ->execute([':property' => $propertyId]);

    $imported = 0;

    foreach ($feeds as $name => $url) {
        try {
            $events = fetch_ics_events($url);
        } catch (Throwable $exception) {
            error_log(sprintf('ICS import failed for property %d (%s): %s', $propertyId, $name, $exception->getMessage()));
            continue;
        }

        foreach ($events as $event) {
            upsert_calendar_block($pdo, [
                'property_id' => $propertyId,
                'source' => 'external_ics',
                'external_uid' => $event['uid'],
                'title' => $event['summary'],
                'start_date' => $event['start'],
                'end_date' => $event['end'],
            ]);
            $imported++;
        }
    }

    $stmtUpdate = $pdo->prepare('UPDATE property_calendars SET last_sync_at = NOW() WHERE id = :id');
    $stmtUpdate->execute([':id' => $calendar['id']]);

    return ['imported' => $imported, 'feeds' => count($feeds)];
}

/**
 * Fetches and parses ICS feed into events.
 *
 * @return array<int, array{uid:string,start:string,end:string,summary:string}>
 */
function fetch_ics_events(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false || trim($raw) === '') {
        throw new RuntimeException('Unable to download ICS feed.');
    }

    $lines = preg_split('/\r\n|\n|\r/', $raw);
    $events = [];
    $current = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === 'BEGIN:VEVENT') {
            $current = [];
            continue;
        }
        if ($line === 'END:VEVENT') {
            if (!empty($current['DTSTART']) && !empty($current['DTEND'])) {
                $events[] = [
                    'uid' => $current['UID'] ?? ('external-' . uniqid()),
                    'start' => normalise_ics_date($current['DTSTART']),
                    'end' => normalise_ics_date($current['DTEND']),
                    'summary' => $current['SUMMARY'] ?? 'External booking',
                ];
            }
            $current = [];
            continue;
        }
        if ($line === '' || strpos($line, ':') === false) {
            continue;
        }
        [$key, $value] = explode(':', $line, 2);
        $key = strtoupper($key);
        $current[$key] = $value;
    }

    return array_filter($events, static function (array $event): bool {
        return $event['start'] !== '' && $event['end'] !== '';
    });
}

/**
 * Normalises ICS date/time values to Y-m-d (exclusive end handled by caller).
 */
function normalise_ics_date(string $value): string
{
    $value = trim($value);
    if (strpos($value, 'T') !== false) {
        $dt = DateTimeImmutable::createFromFormat('Ymd\THis', substr($value, 0, 15), new DateTimeZone('UTC'));
        if ($dt === false) {
            $dt = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
        }
    } else {
        $dt = DateTimeImmutable::createFromFormat('Ymd', substr($value, 0, 8), new DateTimeZone('UTC'));
    }

    if (!$dt) {
        return '';
    }

    return $dt->format('Y-m-d');
}
