<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class GeoFieldValidator
{
    /**
     * @return array<string, mixed> GeoJSON object
     */
    public static function parseAndValidate(string $fieldType, mixed $raw, string $fieldName = 'location'): array
    {
        if (is_array($raw)) {
            $data = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $data = json_decode($raw, true);
            if (! is_array($data)) {
                throw ValidationException::withMessages([$fieldName => 'Invalid location data.']);
            }
        } else {
            throw ValidationException::withMessages([$fieldName => 'Location is required.']);
        }

        try {
            return match ($fieldType) {
                'geopoint' => self::validatePoint($data),
                'geotrace' => self::validateLine($data),
                'geoshape' => self::validatePolygon($data),
                default => $data,
            };
        } catch (ValidationException $e) {
            $messages = $e->errors();
            $first = reset($messages);
            throw ValidationException::withMessages([
                $fieldName => is_array($first) ? ($first[0] ?? 'Invalid location.') : 'Invalid location.',
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private static function validatePoint(array $data): array
    {
        $coords = $data['coordinates'] ?? null;
        if ($data['type'] !== 'Point' || ! is_array($coords) || count($coords) < 2) {
            throw ValidationException::withMessages(['location' => 'Capture a point on the map or use your location.']);
        }

        return [
            'type' => 'Point',
            'coordinates' => [(float) $coords[0], (float) $coords[1]],
            'properties' => $data['properties'] ?? [],
        ];
    }

    /** @param array<string, mixed> $data */
    private static function validateLine(array $data): array
    {
        $coords = $data['coordinates'] ?? null;
        if ($data['type'] !== 'LineString' || ! is_array($coords) || count($coords) < 2) {
            throw ValidationException::withMessages(['location' => 'Add at least two points for the trace.']);
        }

        return [
            'type' => 'LineString',
            'coordinates' => array_map(fn ($c) => [(float) $c[0], (float) $c[1]], $coords),
            'properties' => $data['properties'] ?? [],
        ];
    }

    /** @param array<string, mixed> $data */
    private static function validatePolygon(array $data): array
    {
        $rings = $data['coordinates'] ?? null;
        if ($data['type'] !== 'Polygon' || ! is_array($rings) || ! isset($rings[0]) || count($rings[0]) < 4) {
            throw ValidationException::withMessages(['location' => 'Add at least three corners to close the shape.']);
        }

        $ring = array_map(fn ($c) => [(float) $c[0], (float) $c[1]], $rings[0]);
        $first = $ring[0];
        $last = $ring[count($ring) - 1];
        if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
            $ring[] = $first;
        }

        return [
            'type' => 'Polygon',
            'coordinates' => [$ring],
            'properties' => $data['properties'] ?? [],
        ];
    }

    /** @param array<string, mixed> $geojson */
    public static function representativeLatLng(array $geojson): array
    {
        $type = $geojson['type'] ?? '';
        $coords = $geojson['coordinates'] ?? [];

        if ($type === 'Point') {
            return ['lat' => (float) $coords[1], 'lng' => (float) $coords[0]];
        }
        if ($type === 'LineString' && count($coords) > 0) {
            $c = $coords[0];

            return ['lat' => (float) $c[1], 'lng' => (float) $c[0]];
        }
        if ($type === 'Polygon' && isset($coords[0][0])) {
            $c = $coords[0][0];

            return ['lat' => (float) $c[1], 'lng' => (float) $c[0]];
        }

        return ['lat' => null, 'lng' => null];
    }
}
