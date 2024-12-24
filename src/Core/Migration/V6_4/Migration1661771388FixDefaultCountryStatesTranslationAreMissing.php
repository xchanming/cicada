<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1661771388FixDefaultCountryStatesTranslationAreMissing extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1661771388;
    }

    public function update(Connection $connection): void
    {
        $data = [
            // US
            'US-AL' => 'Alabama',
            'US-AK' => 'Alaska',
            'US-AZ' => 'Arizona',
            'US-AR' => 'Arkansas',
            'US-CA' => 'California',
            'US-CO' => 'Colorado',
            'US-CT' => 'Connecticut',
            'US-DE' => 'Delaware',
            'US-FL' => 'Florida',
            'US-GA' => 'Georgia',
            'US-HI' => 'Hawaii',
            'US-ID' => 'Idaho',
            'US-IL' => 'Illinois',
            'US-IN' => 'Indiana',
            'US-IA' => 'Iowa',
            'US-KS' => 'Kansas',
            'US-KY' => 'Kentucky',
            'US-LA' => 'Louisiana',
            'US-ME' => 'Maine',
            'US-MD' => 'Maryland',
            'US-MA' => 'Massachusetts',
            'US-MI' => 'Michigan',
            'US-MN' => 'Minnesota',
            'US-MS' => 'Mississippi',
            'US-MO' => 'Missouri',
            'US-MT' => 'Montana',
            'US-NE' => 'Nebraska',
            'US-NV' => 'Nevada',
            'US-NH' => 'New Hampshire',
            'US-NJ' => 'New Jersey',
            'US-NM' => 'New Mexico',
            'US-NY' => 'New York',
            'US-NC' => 'North Carolina',
            'US-ND' => 'North Dakota',
            'US-OH' => 'Ohio',
            'US-OK' => 'Oklahoma',
            'US-OR' => 'Oregon',
            'US-PA' => 'Pennsylvania',
            'US-RI' => 'Rhode Island',
            'US-SC' => 'South Carolina',
            'US-SD' => 'South Dakota',
            'US-TN' => 'Tennessee',
            'US-TX' => 'Texas',
            'US-UT' => 'Utah',
            'US-VT' => 'Vermont',
            'US-VA' => 'Virginia',
            'US-WA' => 'Washington',
            'US-WV' => 'West Virginia',
            'US-WI' => 'Wisconsin',
            'US-WY' => 'Wyoming',
            'US-DC' => 'District of Columbia',

            // German
            'DE-BW' => 'Baden-Württemberg',
            'DE-BY' => 'Bayern',
            'DE-BE' => 'Berlin',
            'DE-BB' => 'Brandenburg',
            'DE-HB' => 'Bremen',
            'DE-HH' => 'Hamburg',
            'DE-HE' => 'Hessen',
            'DE-NI' => 'Niedersachsen',
            'DE-MV' => 'Mecklenburg-Vorpommern',
            'DE-NW' => 'Nordrhein-Westfalen',
            'DE-RP' => 'Rheinland-Pfalz',
            'DE-SL' => 'Saarland',
            'DE-SN' => 'Sachsen',
            'DE-ST' => 'Sachsen-Anhalt',
            'DE-SH' => 'Schleswig-Holstein',
            'DE-TH' => 'Thüringen',

            // Great Britain
            'GB-ENG' => 'England',
            'GB-NIR' => 'Northern Ireland',
            'GB-SCT' => 'Scotland',
            'GB-WLS' => 'Wales',
            'GB-EAW' => 'England and Wales',
            'GB-GBN' => 'Great Britain',
            'GB-UKM' => 'United Kingdom',
            'GB-BKM' => 'Buckinghamshire',
            'GB-CAM' => 'Cambridgeshire',
            'GB-CMA' => 'Cumbria',
            'GB-DBY' => 'Derbyshire',
            'GB-DEV' => 'Devon',
            'GB-DOR' => 'Dorset',
            'GB-ESX' => 'East Sussex',
            'GB-ESS' => 'Essex',
            'GB-GLS' => 'Gloucestershire',
            'GB-HAM' => 'Hampshire',
            'GB-HRT' => 'Hertfordshire',
            'GB-KEN' => 'Kent',
            'GB-LAN' => 'Lancashire',
            'GB-LEC' => 'Leicestershire',
            'GB-LIN' => 'Lincolnshire',
            'GB-NFK' => 'Norfolk',
            'GB-NYK' => 'North Yorkshire',
            'GB-NTH' => 'Northamptonshire',
            'GB-NTT' => 'Nottinghamshire',
            'GB-OXF' => 'Oxfordshire',
            'GB-SOM' => 'Somerset',
            'GB-STS' => 'Staffordshire',
            'GB-SFK' => 'Suffolk',
            'GB-SRY' => 'Surrey',
            'GB-WAR' => 'Warwickshire',
            'GB-WSX' => 'West Sussex',
            'GB-WOR' => 'Worcestershire',
            'GB-LND' => 'London, City of',
            'GB-BDG' => 'Barking and Dagenham',
            'GB-BNE' => 'Barnet',
            'GB-BEX' => 'Bexley',
            'GB-BEN' => 'Brent',
            'GB-BRY' => 'Bromley',
            'GB-CMD' => 'Camden',
            'GB-CRY' => 'Croydon',
            'GB-EAL' => 'Ealing',
            'GB-ENF' => 'Enfield',
            'GB-GRE' => 'Greenwich',
            'GB-HCK' => 'Hackney',
            'GB-HMF' => 'Hammersmith and Fulham',
            'GB-HRY' => 'Haringey',
            'GB-HRW' => 'Harrow',
            'GB-HAV' => 'Havering',
            'GB-HIL' => 'Hillingdon',
            'GB-HNS' => 'Hounslow',
            'GB-ISL' => 'Islington',
            'GB-KEC' => 'Kensington and Chelsea',
            'GB-KTT' => 'Kingston upon Thames',
            'GB-LBH' => 'Lambeth',
            'GB-LEW' => 'Lewisham',
            'GB-MRT' => 'Merton',
            'GB-NWM' => 'Newham',
            'GB-RDB' => 'Redbridge',
            'GB-RIC' => 'Richmond upon Thames',
            'GB-SWK' => 'Southwark',
            'GB-STN' => 'Sutton',
            'GB-TWH' => 'Tower Hamlets',
            'GB-WFT' => 'Waltham Forest',
            'GB-WND' => 'Wandsworth',
            'GB-WSM' => 'Westminster',
            'GB-BNS' => 'Barnsley',
            'GB-BIR' => 'Birmingham',
            'GB-BOL' => 'Bolton',
            'GB-BRD' => 'Bradford',
            'GB-BUR' => 'Bury',
            'GB-CLD' => 'Calderdale',
            'GB-COV' => 'Coventry',
            'GB-DNC' => 'Doncaster',
            'GB-DUD' => 'Dudley',
            'GB-GAT' => 'Gateshead',
            'GB-KIR' => 'Kirklees',
            'GB-KWL' => 'Knowsley',
            'GB-LDS' => 'Leeds',
            'GB-LIV' => 'Liverpool',
            'GB-MAN' => 'Manchester',
            'GB-NET' => 'Newcastle upon Tyne',
            'GB-NTY' => 'North Tyneside',
            'GB-OLD' => 'Oldham',
            'GB-RCH' => 'Rochdale',
            'GB-ROT' => 'Rotherham',
            'GB-SHN' => 'St. Helens',
            'GB-SLF' => 'Salford',
            'GB-SAW' => 'Sandwell',
            'GB-SFT' => 'Sefton',
            'GB-SHF' => 'Sheffield',
            'GB-SOL' => 'Solihull',
            'GB-STY' => 'South Tyneside',
            'GB-SKP' => 'Stockport',
            'GB-SND' => 'Sunderland',
            'GB-TAM' => 'Tameside',
            'GB-TRF' => 'Trafford',
            'GB-WKF' => 'Wakefield',
            'GB-WLL' => 'Walsall',
            'GB-WGN' => 'Wigan',
            'GB-WRL' => 'Wirral',
            'GB-WLV' => 'Wolverhampton',
            'GB-BAS' => 'Bath and North East Somerset',
            'GB-BDF' => 'Bedford',
            'GB-BBD' => 'Blackburn with Darwen',
            'GB-BPL' => 'Blackpool',
            'GB-BMH' => 'Bournemouth',
            'GB-BRC' => 'Bracknell Forest',
            'GB-BNH' => 'Brighton and Hove',
            'GB-BST' => 'Bristol, City of',
            'GB-CBF' => 'Central Bedfordshire',
            'GB-CHE' => 'Cheshire East',
            'GB-CHW' => 'Cheshire West and Chester',
            'GB-CON' => 'Cornwall',
            'GB-DAL' => 'Darlington',
            'GB-DER' => 'Derby',
            'GB-DUR' => 'Durham County',
            'GB-ERY' => 'East Riding of Yorkshire',
            'GB-HAL' => 'Halton',
            'GB-HPL' => 'Hartlepool',
            'GB-HEF' => 'Herefordshire',
            'GB-IOW' => 'Isle of Wight',
            'GB-IOS' => 'Isles of Scilly',
            'GB-KHL' => 'Kingston upon Hull',
            'GB-LCE' => 'Leicester',
            'GB-LUT' => 'Luton',
            'GB-MDW' => 'Medway',
            'GB-MDB' => 'Middlesbrough',
            'GB-MIK' => 'Milton Keynes',
            'GB-NEL' => 'North East Lincolnshire',
            'GB-NLN' => 'North Lincolnshire',
            'GB-NSM' => 'North Somerset',
            'GB-NBL' => 'Northumberland',
            'GB-NGM' => 'Nottingham',
            'GB-PTE' => 'Peterborough',
            'GB-PLY' => 'Plymouth',
            'GB-POL' => 'Poole',
            'GB-POR' => 'Portsmouth',
            'GB-RDG' => 'Reading',
            'GB-RCC' => 'Redcar and Cleveland',
            'GB-RUT' => 'Rutland',
            'GB-SHR' => 'Shropshire',
            'GB-SLG' => 'Slough',
            'GB-SGC' => 'South Gloucestershire',
            'GB-STH' => 'Southampton',
            'GB-SOS' => 'Southend-on-Sea',
            'GB-STT' => 'Stockton-on-Tees',
            'GB-STE' => 'Stoke-on-Trent',
            'GB-SWD' => 'Swindon',
            'GB-TFW' => 'Telford and Wrekin',
            'GB-THR' => 'Thurrock',
            'GB-TOB' => 'Torbay',
            'GB-WRT' => 'Warrington',
            'GB-WBK' => 'West Berkshire',
            'GB-WIL' => 'Wiltshire',
            'GB-WNM' => 'Windsor and Maidenhead',
            'GB-WOK' => 'Wokingham',
            'GB-YOR' => 'York',
            'GB-ANN' => 'Antrim and Newtownabbey',
            'GB-AND' => 'Ards and North Down',
            'GB-ABC' => 'Armagh, Banbridge and Craigavon',
            'GB-BFS' => 'Belfast',
            'GB-CCG' => 'Causeway Coast and Glens',
            'GB-DRS' => 'Derry and Strabane',
            'GB-FMO' => 'Fermanagh and Omagh',
            'GB-LBC' => 'Lisburn and Castlereagh',
            'GB-MEA' => 'Mid and East Antrim',
            'GB-MUL' => 'Mid Ulster',
            'GB-NMD' => 'Newry, Mourne and Down',
            'GB-ABE' => 'Aberdeen City',
            'GB-ABD' => 'Aberdeenshire',
            'GB-ANS' => 'Angus',
            'GB-AGB' => 'Argyll and Bute',
            'GB-CLK' => 'Clackmannanshire',
            'GB-DGY' => 'Dumfries and Galloway',
            'GB-DND' => 'Dundee City',
            'GB-EAY' => 'East Ayrshire',
            'GB-EDU' => 'East Dunbartonshire',
            'GB-ELN' => 'East Lothian',
            'GB-ERW' => 'East Renfrewshire',
            'GB-EDH' => 'Edinburgh, City of',
            'GB-ELS' => 'Eilean Siar',
            'GB-FAL' => 'Falkirk',
            'GB-FIF' => 'Fife',
            'GB-GLG' => 'Glasgow City',
            'GB-HLD' => 'Highland',
            'GB-IVC' => 'Inverclyde',
            'GB-MLN' => 'Midlothian',
            'GB-MRY' => 'Moray',
            'GB-NAY' => 'North Ayrshire',
            'GB-NLK' => 'North Lanarkshire',
            'GB-ORK' => 'Orkney Islands',
            'GB-PKN' => 'Perth and Kinross',
            'GB-RFW' => 'Renfrewshire',
            'GB-SCB' => 'Scottish Borders, The',
            'GB-ZET' => 'Shetland Islands',
            'GB-SAY' => 'South Ayrshire',
            'GB-SLK' => 'South Lanarkshire',
            'GB-STG' => 'Stirling',
            'GB-WDU' => 'West Dunbartonshire',
            'GB-WLN' => 'West Lothian',
            'GB-BGW' => 'Blaenau Gwent',
            'GB-BGE' => 'Bridgend',
            'GB-CAY' => 'Caerphilly',
            'GB-CRF' => 'Cardiff',
            'GB-CMN' => 'Carmarthenshire',
            'GB-CGN' => 'Ceredigion',
            'GB-CWY' => 'Conwy',
            'GB-DEN' => 'Denbighshire',
            'GB-FLN' => 'Flintshire',
            'GB-GWN' => 'Gwynedd',
            'GB-AGY' => 'Isle of Anglesey',
            'GB-MTY' => 'Merthyr Tydfil',
            'GB-MON' => 'Monmouthshire',
            'GB-NTL' => 'Neath Port Talbot',
            'GB-NWP' => 'Newport',
            'GB-PEM' => 'Pembrokeshire',
            'GB-POW' => 'Powys',
            'GB-RCT' => 'Rhondda, Cynon, Taff',
            'GB-SWA' => 'Swansea',
            'GB-TOF' => 'Torfaen',
            'GB-VGL' => 'Vale of Glamorgan, The',
            'GB-WRX' => 'Wrexham',
        ];

        $missingTranslations = $connection->fetchAllKeyValue('
            SELECT id, short_code FROM `country_state`
            WHERE id NOT IN (
                SELECT country_state_id FROM country_state_translation WHERE language_id = :languageId GROUP BY country_state_id
            )', [
            'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
        ]);

        if (empty($missingTranslations)) {
            return;
        }

        $storageDate = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($missingTranslations as $stateId => $shortCode) {
            if (!\array_key_exists($shortCode, $data)) {
                continue;
            }

            $connection->insert('country_state_translation', [
                'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                'country_state_id' => $stateId,
                'name' => $data[$shortCode],
                'created_at' => $storageDate,
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
