<?php

namespace Database\Seeders;

/**
 * Loads India states and districts from {@see database/data/india_states_districts.json}
 * (sourced from sab99r/Indian-States-And-Districts, government district lists).
 *
 * Applies post-processing for current UTs: merged Dadra/Daman/Diu, Ladakh split from J&K,
 * Andaman & Nicobar added, and display names normalized.
 */
final class IndiaGeoData
{
    /**
     * ISO 3166-2:IN subdivision codes (without IN- prefix), keyed by state/UT name as stored in DB.
     *
     * @var array<string, string>
     */
    private const STATE_CODES = [
        'Andaman and Nicobar Islands' => 'AN',
        'Andhra Pradesh' => 'AP',
        'Arunachal Pradesh' => 'AR',
        'Assam' => 'AS',
        'Bihar' => 'BR',
        'Chandigarh' => 'CH',
        'Chhattisgarh' => 'CG',
        'Dadra and Nagar Haveli and Daman and Diu' => 'DH',
        'Delhi' => 'DL',
        'Goa' => 'GA',
        'Gujarat' => 'GJ',
        'Haryana' => 'HR',
        'Himachal Pradesh' => 'HP',
        'Jammu and Kashmir' => 'JK',
        'Jharkhand' => 'JH',
        'Karnataka' => 'KA',
        'Kerala' => 'KL',
        'Ladakh' => 'LA',
        'Lakshadweep' => 'LD',
        'Madhya Pradesh' => 'MP',
        'Maharashtra' => 'MH',
        'Manipur' => 'MN',
        'Meghalaya' => 'ML',
        'Mizoram' => 'MZ',
        'Nagaland' => 'NL',
        'Odisha' => 'OR',
        'Puducherry' => 'PY',
        'Punjab' => 'PB',
        'Rajasthan' => 'RJ',
        'Sikkim' => 'SK',
        'Tamil Nadu' => 'TN',
        'Telangana' => 'TG',
        'Tripura' => 'TR',
        'Uttarakhand' => 'UK',
        'Uttar Pradesh' => 'UP',
        'West Bengal' => 'WB',
    ];

    /**
     * @return list<array{name: string, code: string, districts: list<string>}>
     */
    public static function statesWithDistricts(): array
    {
        $path = dirname(__DIR__).'/data/india_states_districts.json';
        if (! is_readable($path)) {
            throw new \RuntimeException("India geo JSON missing or unreadable: {$path}");
        }

        $raw = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $byName = [];

        foreach ($raw['states'] as $row) {
            $name = $row['state'];
            $districts = [];
            foreach ($row['districts'] as $d) {
                $districts[] = trim(html_entity_decode((string) $d, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            $byName[$name] = array_values(array_unique($districts));
        }

        self::mergeDadraDamanDiu($byName);
        self::splitLadakhFromJammuKashmir($byName);
        self::renameUtLabels($byName);
        $byName['Andaman and Nicobar Islands'] = ['Nicobar', 'North and Middle Andaman', 'South Andaman'];

        $out = [];
        foreach (self::STATE_CODES as $name => $code) {
            if (! isset($byName[$name])) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'code' => $code,
                'districts' => $byName[$name],
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, list<string>>  $byName
     */
    private static function mergeDadraDamanDiu(array &$byName): void
    {
        $dnh = $byName['Dadra and Nagar Haveli (UT)'] ?? [];
        $dd = $byName['Daman and Diu (UT)'] ?? [];
        unset($byName['Dadra and Nagar Haveli (UT)'], $byName['Daman and Diu (UT)']);
        $byName['Dadra and Nagar Haveli and Daman and Diu'] = array_values(array_unique([...$dnh, ...$dd]));
    }

    /**
     * @param  array<string, list<string>>  $byName
     */
    private static function splitLadakhFromJammuKashmir(array &$byName): void
    {
        if (! isset($byName['Jammu and Kashmir'])) {
            return;
        }

        $ladakh = ['Leh', 'Kargil'];
        $remaining = [];
        foreach ($byName['Jammu and Kashmir'] as $d) {
            if (! in_array($d, $ladakh, true)) {
                $remaining[] = $d;
            }
        }
        $byName['Jammu and Kashmir'] = array_values(array_unique($remaining));
        $byName['Ladakh'] = $ladakh;
    }

    /**
     * @param  array<string, list<string>>  $byName
     */
    private static function renameUtLabels(array &$byName): void
    {
        $map = [
            'Chandigarh (UT)' => 'Chandigarh',
            'Delhi (NCT)' => 'Delhi',
            'Lakshadweep (UT)' => 'Lakshadweep',
            'Puducherry (UT)' => 'Puducherry',
        ];
        foreach ($map as $from => $to) {
            if (isset($byName[$from])) {
                $byName[$to] = $byName[$from];
                unset($byName[$from]);
            }
        }
    }
}
