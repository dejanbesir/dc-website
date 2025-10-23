<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/booking.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
    return;
}

try {
    $required = ['property_id','arrival_date','departure_date','email','full_name','address','city','postal_code','country','phone'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new InvalidArgumentException('Missing field: ' . $field);
        }
    }

    $arrival = $input['arrival_date'];
    $departure = $input['departure_date'];
    if ($departure <= $arrival) {
        throw new InvalidArgumentException('Departure must be after arrival.');
    }

    $payload = [
        'property_id' => (int) $input['property_id'],
        'arrival_date' => $arrival,
        'departure_date' => $departure,
        'adults' => max(1, (int) ($input['adults'] ?? 1)),
        'children' => max(0, (int) ($input['children'] ?? 0)),
        'infants' => max(0, (int) ($input['infants'] ?? 0)),
        'email' => strtolower(trim((string) $input['email'])),
        'email_token' => bin2hex(random_bytes(32)),
        'full_name' => trim((string) $input['full_name']),
        'address' => trim((string) $input['address']),
        'city' => trim((string) $input['city']),
        'region' => trim((string) ($input['region'] ?? '')),
        'postal_code' => trim((string) $input['postal_code']),
        'country' => trim((string) $input['country']),
        'phone' => trim((string) $input['phone']),
    ];

    $travellers = [];
    foreach (($input['travellers'] ?? []) as $traveller) {
        $type = $traveller['type'] ?? 'adult';
        if (!in_array($type, ['adult','child','infant'], true)) {
            $type = 'adult';
        }
        $travellers[] = [
            'type' => $type,
            'age' => isset($traveller['age']) ? (int) $traveller['age'] : null,
        ];
    }

    $result = api_create_booking($payload, $travellers);

    echo json_encode([
        'success' => true,
        'reference' => $result['reference'],
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}
