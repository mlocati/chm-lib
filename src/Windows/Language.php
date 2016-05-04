<?php

namespace CHMLib\Windows;

/**
 * Windows language details.
 */
class Language
{
    /**
     * Windows language ID.
     *
     * @var int
     */
    protected $id;

    /**
     * ISO code of the language.
     *
     * @var string
     */
    protected $languageCode;

    /**
     * ISO code of the Country.
     *
     * @var string
     */
    protected $countryCode;

    /**
     * English name of the language.
     *
     * @var string
     */
    protected $languageName;

    /**
     * English name of the Country.
     *
     * @var string
     */
    protected $countryName;

    /**
     * Initializes the instance.
     *
     * @param int $id The Windows language ID.
     */
    public function __construct($id)
    {
        $this->id = (int) $id;
        $map = static::getMap();
        $info = isset($map[$this->id]) ? $map[$this->id] : null;
        $this->languageCode = ($info === null) ? '' : $info[0];
        $this->countryCode = ($info === null) ? '' : $info[1];
        $this->languageName = ($info === null) ? '' : $info[2];
        $this->countryName = ($info === null) ? '' : $info[3];
    }

    /**
     * Get the Windows language ID.
     *
     * @return int
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get the ISO code of the language (empty string if not available).
     *
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * Get the ISO code of the Country (empty string if not available).
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Get the English name of the language (empty string if not available).
     *
     * @return string
     */
    public function getLanguageName()
    {
        return $this->languageName;
    }

    /**
     * Get the English name of the Country (empty string if not available).
     *
     * @return string
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * Returns a string representation of this instance.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->languageName === '' && $this->countryName === '') {
            return 'ID:'.$this->id;
        } elseif ($this->countryName === '') {
            return $this->languageName;
        } elseif ($this->languageName === '') {
            return $this->countryName;
        } else {
            return $this->languageName.' ('.$this->countryName.')';
        }
    }

    /**
     * Cache the info about the recognized Windows language IDs.
     *
     * @var array|null
     */
    protected static $map = null;

    /**
     * Get all the recognized Windows language IDs.
     *
     * @return array
     */
    protected static function getMap()
    {
        if (!isset(self::$map)) {
            self::$map = array(
                0x0436 => array('af', 'ZA', 'Afrikaans', 'South Africa'),
                0x041C => array('sq', 'AL', 'Albanian', 'Albania'),
                0x0484 => array('gsw', 'FR', 'Alsatian', 'France'),
                0x045E => array('am', 'ET', 'Amharic', 'Ethiopia'),
                0x1401 => array('ar', 'DZ', 'Arabic', 'Algeria'),
                0x3C01 => array('ar', 'BH', 'Arabic', 'Bahrain'),
                0x0C01 => array('ar', 'EG', 'Arabic', 'Egypt'),
                0x0801 => array('ar', 'IQ', 'Arabic', 'Iraq'),
                0x2C01 => array('ar', 'JO', 'Arabic', 'Jordan'),
                0x3401 => array('ar', 'KW', 'Arabic', 'Kuwait'),
                0x3001 => array('ar', 'LB', 'Arabic', 'Lebanon'),
                0x1001 => array('ar', 'LY', 'Arabic', 'Libya'),
                0x1801 => array('ar', 'MA', 'Arabic', 'Morocco'),
                0x2001 => array('ar', 'OM', 'Arabic', 'Oman'),
                0x4001 => array('ar', 'QA', 'Arabic', 'Qatar'),
                0x0401 => array('ar', 'SA', 'Arabic', 'Saudi Arabia'),
                0x2801 => array('ar', 'SY', 'Arabic', 'Syria'),
                0x1C01 => array('ar', 'TN', 'Arabic', 'Tunisia'),
                0x3801 => array('ar', 'AE', 'Arabic', 'U.A.E.'),
                0x2401 => array('ar', 'YE', 'Arabic', 'Yemen'),
                0x042B => array('hy', 'AM', 'Armenian', 'Armenia'),
                0x044D => array('as', 'IN', 'Assamese', 'India'),
                0x082C => array('az', 'AZ', 'Azerbaijani', 'Azerbaijan, Cyrillic'),
                0x042C => array('az', 'AZ', 'Azerbaijani', 'Azerbaijan, Latin'),
                0x0445 => array('bn', 'BD', 'Bangla', 'Bangladesh'),
                0x046D => array('ba', 'RU', 'Bashkir', 'Russia'),
                0x0423 => array('be', 'BY', 'Belarusian', 'Belarus'),
                0x201A => array('bs', 'BA', 'Bosnian', 'Bosnia and Herzegovina, Cyrillic'),
                0x141A => array('bs', 'BA', 'Bosnian', 'Bosnia and Herzegovina, Latin'),
                0x047E => array('br', 'FR', 'Breton', 'France'),
                0x0402 => array('bg', 'BG', 'Bulgarian', 'Bulgaria'),
                0x0492 => array('ku', 'IQ', 'Central Kurdish', 'Iraq'),
                0x0403 => array('ca', 'ES', 'Catalan', 'Spain'),
                0x0C04 => array('zh', 'HK', 'Chinese', 'Hong Kong SAR, PRC'),
                0x1404 => array('zh', 'MO', 'Chinese', 'Macao SAR'),
                0x1004 => array('zh', 'SG', 'Chinese', 'Singapore'),
                0x0004 => array('zh', 'CN', 'Chinese', 'Simplified'),
                0x7C04 => array('zh', 'TW', 'Chinese', 'Traditional'),
                0x0483 => array('co', 'FR', 'Corsican', 'France'),
                0x101A => array('hr', 'BA', 'Croatian', 'Bosnia and Herzegovina, Latin'),
                0x041A => array('hr', 'HR', 'Croatian', 'Croatia'),
                0x0405 => array('cs', 'CZ', 'Czech', 'Czech Republic'),
                0x0406 => array('da', 'DK', 'Danish', 'Denmark'),
                0x048C => array('prs', 'AF', 'Dari', 'Afghanistan'),
                0x0465 => array('dv', 'MV', 'Divehi', 'Maldives'),
                0x0813 => array('nl', 'BE', 'Dutch', 'Belgium'),
                0x0413 => array('nl', 'NL', 'Dutch', 'Netherlands'),
                0x0C09 => array('en', 'AU', 'English', 'Australia'),
                0x2809 => array('en', 'BZ', 'English', 'Belize'),
                0x1009 => array('en', 'CA', 'English', 'Canada'),
                0x2409 => array('en', '29', 'English', 'Caribbean'),
                0x4009 => array('en', 'IN', 'English', 'India'),
                0x1809 => array('en', 'IE', 'English', 'Ireland'),
                0x1809 => array('en', 'IE', 'English', 'Ireland'),
                0x2009 => array('en', 'JM', 'English', 'Jamaica'),
                0x4409 => array('en', 'MY', 'English', 'Malaysia'),
                0x1409 => array('en', 'NZ', 'English', 'New Zealand'),
                0x3409 => array('en', 'PH', 'English', 'Philippines'),
                0x4809 => array('en', 'SG', 'English', 'Singapore'),
                0x1c09 => array('en', 'ZA', 'English', 'South Africa'),
                0x2C09 => array('en', 'TT', 'English', 'Trinidad and Tobago'),
                0x0809 => array('en', 'GB', 'English', 'United Kingdom'),
                0x0409 => array('en', 'US', 'English', 'United States'),
                0x3009 => array('en', 'ZW', 'English', 'Zimbabwe'),
                0x0425 => array('et', 'EE', 'Estonian', 'Estonia'),
                0x0438 => array('fo', 'FO', 'Faroese', 'Faroe Islands'),
                0x0464 => array('fil', 'PH', 'Filipino', 'Philippines'),
                0x040B => array('fi', 'FI', 'Finnish', 'Finland'),
                0x080c => array('fr', 'BE', 'French', 'Belgium'),
                0x0C0C => array('fr', 'CA', 'French', 'Canada'),
                0x040c => array('fr', 'FR', 'French', 'France'),
                0x140C => array('fr', 'LU', 'French', 'Luxembourg'),
                0x180C => array('fr', 'MC', 'French', 'Monaco'),
                0x100C => array('fr', 'CH', 'French', 'Switzerland'),
                0x0462 => array('fy', 'NL', 'Frisian', 'Netherlands'),
                0x0456 => array('gl', 'ES', 'Galician', 'Spain'),
                0x0437 => array('ka', 'GE', 'Georgian', 'Georgia'),
                0x0C07 => array('de', 'AT', 'German', 'Austria'),
                0x0407 => array('de', 'DE', 'German', 'Germany'),
                0x1407 => array('de', 'LI', 'German', 'Liechtenstein'),
                0x1007 => array('de', 'LU', 'German', 'Luxembourg'),
                0x0807 => array('de', 'CH', 'German', 'Switzerland'),
                0x0408 => array('el', 'GR', 'Greek', 'Greece'),
                0x046F => array('kl', 'GL', 'Greenlandic', 'Greenland'),
                0x0447 => array('gu', 'IN', 'Gujarati', 'India'),
                0x0468 => array('ha', 'NG', 'Hausa', 'Nigeria'),
                0x0475 => array('haw', 'US', 'Hawiian', 'United States'),
                0x040D => array('he', 'IL', 'Hebrew', 'Israel'),
                0x0439 => array('hi', 'IN', 'Hindi', 'India'),
                0x040E => array('hu', 'HU', 'Hungarian', 'Hungary'),
                0x040F => array('is', 'IS', 'Icelandic', 'Iceland'),
                0x0470 => array('ig', 'NG', 'Igbo', 'Nigeria'),
                0x0421 => array('id', 'ID', 'Indonesian', 'Indonesia'),
                0x085D => array('iu', 'CA', 'Inuktitut', 'Canada, Latin'),
                0x045D => array('iu', 'CA', 'Inuktitut', 'Canada, Canadian Syllabics'),
                0x083C => array('ga', 'IE', 'Irish', 'Ireland'),
                0x0434 => array('xh', 'ZA', 'isiXhosa', 'South Africa'),
                0x0435 => array('zu', 'ZA', 'isiZulu', 'South Africa'),
                0x0410 => array('it', 'IT', 'Italian', 'Italy'),
                0x0810 => array('it', 'CH', 'Italian', 'Switzerland'),
                0x0411 => array('ja', 'JP', 'Japanese', 'Japan'),
                0x044B => array('kn', 'IN', 'Kannada', 'India'),
                0x043F => array('kk', 'KZ', 'Kazakh', 'Kazakhstan'),
                0x0453 => array('kh', 'KH', 'Khmer', 'Cambodia'),
                0x0486 => array('qut', 'GT', 'K\'iche', 'Guatemala'),
                0x0487 => array('rw', 'RW', 'Kinyarwanda', 'Rwanda'),
                0x0457 => array('kok', 'IN', 'Konkani', 'India'),
                0x0412 => array('ko', 'KR', 'Korean', 'Korea'),
                0x0440 => array('ky', 'KG', 'Kyrgyz', 'Kyrgyzstan'),
                0x0454 => array('lo', 'LA', 'Lao', 'Lao PDR'),
                0x0426 => array('lv', 'LV', 'Latvian', 'Latvia'),
                0x0427 => array('lt', 'LT', 'Lithuanian', 'Lithuanian'),
                0x082E => array('dsb', 'DE', 'Lower Sorbian', 'Germany'),
                0x046E => array('lb', 'LU', 'Luxembourgish', 'Luxembourg'),
                0x042F => array('mk', 'MK', 'Macedonian', 'Macedonia - FYROM'),
                0x083E => array('ms', 'BN', 'Malay', 'Brunei Darassalam'),
                0x043e => array('ms', 'MY', 'Malay', 'Malaysia'),
                0x044C => array('ml', 'IN', 'Malayalam', 'India'),
                0x043A => array('mt', 'MT', 'Maltese', 'Malta'),
                0x0481 => array('mi', 'NZ', 'Maori', 'New Zealand'),
                0x047A => array('arn', 'CL', 'Mapudungun', 'Chile'),
                0x044E => array('mr', 'IN', 'Marathi', 'India'),
                0x047C => array('moh', 'CA', 'Mohawk', 'Canada'),
                0x0450 => array('mn', 'MN', 'Mongolian', 'Mongolia, Cyrillic'),
                0x0850 => array('mn', 'MN', 'Mongolian', 'Mongolia, Mong'),
                0x0461 => array('ne', 'NP', 'Nepali', 'Nepal'),
                0x0414 => array('no', 'NO', 'Norwegian', 'Bokmål, Norway'),
                0x0814 => array('no', 'NO', 'Norwegian', 'Nynorsk, Norway'),
                0x0482 => array('oc', 'FR', 'Occitan', 'France'),
                0x0448 => array('or', 'IN', 'Odia', 'India'),
                0x0463 => array('ps', 'AF', 'Pashto', 'Afghanistan'),
                0x0429 => array('fa', 'IR', 'Persian', 'Iran'),
                0x0415 => array('pl', 'PL', 'Polish', 'Poland'),
                0x0416 => array('pt', 'BR', 'Portuguese', 'Brazil'),
                0x0816 => array('pt', 'PT', 'Portuguese', 'Portugal'),
                0x0867 => array('ff', 'SN', 'Pular', 'Senegal'),
                0x0446 => array('pa', 'IN', 'Punjabi', 'India, Gurmukhi script'),
                0x0846 => array('pa', 'PK', 'Punjabi', 'Pakistan, Arabic script'),
                0x046B => array('quz', 'BO', 'Quechua', 'Bolivia'),
                0x086B => array('quz', 'EC', 'Quechua', 'Ecuador'),
                0x0C6B => array('quz', 'PE', 'Quechua', 'Peru'),
                0x0418 => array('ro', 'RO', 'Romanian', 'Romania'),
                0x0417 => array('rm', 'CH', 'Romansh', 'Switzerland'),
                0x0419 => array('ru', 'RU', 'Russian', 'Russia'),
                0x0485 => array('sah', 'RU', 'Sakha', 'Russia'),
                0x243B => array('smn', 'FI', 'Sami', 'Inari, Finland'),
                0x103B => array('smj', 'NO', 'Sami', 'Lule, Norway'),
                0x143B => array('smj', 'SE', 'Sami', 'Lule, Sweden'),
                0x0C3B => array('se', 'FI', 'Sami', 'Northern, Finland'),
                0x043B => array('se', 'NO', 'Sami', 'Northern, Norway'),
                0x083B => array('se', 'SE', 'Sami', 'Northern, Sweden'),
                0x203B => array('sms', 'FI', 'Sami', 'Skolt, Finland'),
                0x183B => array('sma', 'NO', 'Sami', 'Southern, Norway'),
                0x1C3B => array('sma', 'SE', 'Sami', 'Southern, Sweden'),
                0x044F => array('sa', 'IN', 'Sanskrit', 'India'),
                0x1C1A => array('sr', 'BA', 'Serbian', 'Bosnia and Herzegovina, Cyrillic'),
                0x181A => array('sr', 'BA', 'Serbian', 'Bosnia and Herzegovina, Latin'),
                0x0C1A => array('sr', 'CS', 'Serbian', 'Serbia and Montenegro - former, Cyrillic'),
                0x081A => array('sr', 'CS', 'Serbian', 'Serbia and Montenegro former, Latin'),
                0x046C => array('nso', 'ZA', 'Sesotho sa Leboa', 'South Africa'),
                0x0832 => array('tn', 'BW', 'Setswana / Tswana', 'Botswana'),
                0x0432 => array('tn', 'ZA', 'Setswana / Tswana', 'South Africa'),
                0x0859 => array('sd', 'PK', 'Sindhi', 'Pakistan'),
                0x045B => array('si', 'LK', 'Sinhala', 'Sri Lanka'),
                0x041B => array('sk', 'SK', 'Slovak', 'Slovakia'),
                0x0424 => array('sl', 'SI', 'Slovenian', 'Slovenia'),
                0x2C0A => array('es', 'AR', 'Spanish', 'Argentina'),
                0x400A => array('es', 'BO', 'Spanish', 'Bolivia'),
                0x340A => array('es', 'CL', 'Spanish', 'Chile'),
                0x240A => array('es', 'CO', 'Spanish', 'Colombia'),
                0x140A => array('es', 'CR', 'Spanish', 'Costa Rica'),
                0x1C0A => array('es', 'DO', 'Spanish', 'Dominican Republic'),
                0x300A => array('es', 'EC', 'Spanish', 'Ecuador'),
                0x440A => array('es', 'SV', 'Spanish', 'El Salvador'),
                0x100A => array('es', 'GT', 'Spanish', 'Guatemala'),
                0x480A => array('es', 'HN', 'Spanish', 'Honduras'),
                0x080A => array('es', 'MX', 'Spanish', 'Mexico'),
                0x4C0A => array('es', 'NI', 'Spanish', 'Nicaragua'),
                0x180A => array('es', 'PA', 'Spanish', 'Panama'),
                0x3C0A => array('es', 'PY', 'Spanish', 'Paraguay'),
                0x280A => array('es', 'PE', 'Spanish', 'Peru'),
                0x500A => array('es', 'PR', 'Spanish', 'Puerto Rico'),
                0x0C0A => array('es', 'ES', 'Spanish', 'Spain, Modern Sort'),
                0x040A => array('es', 'ES', 'Spanish', 'Spain, Traditional Sort'),
                0x540A => array('es', 'US', 'Spanish', 'United States'),
                0x380A => array('es', 'UY', 'Spanish', 'Uruguay'),
                0x200A => array('es', 'VE', 'Spanish', 'Venezuela'),
                0x0441 => array('sw', 'KE', 'Swahili', 'Kenya'),
                0x081D => array('sv', 'FI', 'Swedish', 'Finland'),
                0x041D => array('sv', 'SE', 'Swedish', 'Sweden'),
                0x041D => array('sv', 'SE', 'Swedish', 'Sweden'),
                0x045A => array('syr', 'SY', 'Syriac', 'Syria'),
                0x0428 => array('tg', 'TJ', 'Tajik', 'Tajikistan, Cyrillic'),
                0x085F => array('tzm', 'DZ', 'Tamazight', 'Algeria, Latin'),
                0x0449 => array('ta', 'IN', 'Tamil', 'India'),
                0x0849 => array('ta', 'LK', 'Tamil', 'Sri Lanka'),
                0x0444 => array('tt', 'RU', 'Tatar', 'Russia'),
                0x044A => array('te', 'IN', 'Telugu', 'India'),
                0x041E => array('th', 'TH', 'Thai', 'Thailand'),
                0x0451 => array('bo', 'CN', 'Tibetan', 'PRC'),
                0x0873 => array('ti', 'ER', 'Tigrinya', 'Eritrea'),
                0x0473 => array('ti', 'ET', 'Tigrinya', 'Ethiopia'),
                0x041F => array('tr', 'TR', 'Turkish', 'Turkey'),
                0x0442 => array('tk', 'TM', 'Turkmen', 'Turkmenistan'),
                0x0422 => array('uk', 'UA', 'Ukrainian', 'Ukraine'),
                0x042E => array('hsb', 'DE', 'Upper Sorbian', 'Germany'),
                0x0420 => array('ur', 'PK', 'Urdu', 'Pakistan'),
                0x0480 => array('ug', 'CN', 'Uyghur', 'PRC'),
                0x0843 => array('uz', 'UZ', 'Uzbek', 'Uzbekistan, Cyrillic'),
                0x0443 => array('uz', 'UZ', 'Uzbek', 'Uzbekistan, Latin'),
                0x042A => array('vi', 'VN', 'Vietnamese', 'Vietnam'),
                0x0452 => array('cy', 'GB', 'Welsh', 'United Kingdom'),
                0x0488 => array('wo', 'SN', 'Wolof', 'Senegal'),
                0x0478 => array('ii', 'CN', 'Yi', 'PRC'),
                0x046A => array('yo', 'NG', 'Yoruba', 'Nigeria'),
            );
        }

        return self::$map;
    }
}
