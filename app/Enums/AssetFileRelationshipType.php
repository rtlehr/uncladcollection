<?php

namespace App\Enums;

enum AssetFileRelationshipType: string
{
    case Represents = 'represents';
    case PosterFor = 'poster_for';
    case SourceOf = 'source_of';
    case DerivedFrom = 'derived_from';
    case Contains = 'contains';
    case AlternateOf = 'alternate_of';
    case CompanionTo = 'companion_to';

    public function label(): string
    {
        return match ($this) {
            self::Represents => 'Represents',
            self::PosterFor => 'Poster For',
            self::SourceOf => 'Source Of',
            self::DerivedFrom => 'Derived From',
            self::Contains => 'Contains',
            self::AlternateOf => 'Alternate Of',
            self::CompanionTo => 'Companion To',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Represents => 'The source file visually represents the target file.',
            self::PosterFor => 'The source image is the poster or cover for the target video.',
            self::SourceOf => 'The source file was used to create the target file.',
            self::DerivedFrom => 'The source file was derived from the target file.',
            self::Contains => 'The source archive or package contains the target file.',
            self::AlternateOf => 'The source file is an alternate format or variation of the target file.',
            self::CompanionTo => 'The files are related companion deliverables.',
        };
    }
}
