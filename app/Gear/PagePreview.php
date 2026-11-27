<?php

namespace App\Gear;

use Illuminate\Support\Str;

/**
 * Link preview text and image for chat and social embeds, describing what the shared page shows.
 *
 * @phpstan-import-type Profile from GearStatistics
 *
 * @phpstan-type Preview array{description: string, image: string}
 */
class PagePreview
{
    private const array SHORT_STAT_NAMES = ['crit' => 'Crit', 'haste' => 'Haste', 'mastery' => 'Mastery', 'vers' => 'Vers'];

    /**
     * e.g. "74 items available for Blood Death Knight using Crit/Vers."
     *
     * @param  array{name: string, icon: string, specs: array<string, array{name: string, icon: string}>}|null  $class
     * @param  array<int, string>  $itemSets  item set ID => name
     * @return Preview
     */
    public static function gear(GearFilter $filter, int $count, ?array $class, ?string $specKey, string $season, array $itemSets): array
    {
        $spec = $specKey === null ? null : $class['specs'][$specKey];
        $audience = match (true) {
            $spec !== null => "for {$spec['name']} {$class['name']}",
            $class !== null => 'for '.Str::plural($class['name']),
            default => "in {$season}",
        };

        $itemSet = match ($filter->itemSet) {
            null => '',
            true => ' with a set bonus',
            default => " of {$itemSets[$filter->itemSet]}",
        };

        $description = $count.' '.($filter->slot === null ? '' : $filter->slot->label().' ').Str::plural('item', $count)."{$itemSet} available {$audience}";

        if ($filter->stats !== []) {
            $description .= ' using '.implode('/', array_map(fn (string $stat): string => self::SHORT_STAT_NAMES[$stat], $filter->stats));
        }

        if (array_diff(array_keys(GearCatalog::SOURCE_TYPES), $filter->sources) === []) {
            $description .= ' from all sources';
        } elseif (! self::isDefaultSources($filter->sources)) {
            $description .= ' from '.collect(GearCatalog::SOURCE_TYPES)->only($filter->sources)->join(', ', ' and ');
        }

        return ['description' => $description.'.', 'image' => self::icon($spec['icon'] ?? $class['icon'] ?? null)];
    }

    /**
     * @param  array{profile: Profile}  $overall
     * @return Preview
     */
    public static function statistics(array $overall, int $specCount, string $season): array
    {
        return [
            'description' => trim("How {$season} raid, tier set and dungeon loot is spread across {$specCount} specs, slots and secondary stats. ".self::secondarySpread($overall['profile'])),
            'image' => self::icon(null),
        ];
    }

    /**
     * @param  array{profile: Profile}  $statistics
     * @param  array{name: string, specs: array<string, array{name: string, icon: string}>}  $class
     * @return Preview
     */
    public static function specStatistics(array $statistics, array $class, string $specKey): array
    {
        $spec = $class['specs'][$specKey];
        $items = $statistics['profile']['items'];

        return [
            'description' => trim("{$items} raid, tier set and dungeon ".Str::plural('item', $items)." for {$spec['name']} {$class['name']}. ".self::secondarySpread($statistics['profile'])),
            'image' => self::icon($spec['icon']),
        ];
    }

    /**
     * @param  array{name: string, icon: ?string, profile: Profile}  $instance
     * @return Preview
     */
    public static function instanceStatistics(array $instance): array
    {
        $items = $instance['profile']['items'];

        return [
            'description' => trim("{$items} ".Str::plural('item', $items)." from {$instance['name']}. ".self::secondarySpread($instance['profile'])),
            'image' => self::icon($instance['icon']),
        ];
    }

    /**
     * @param  list<string>  $sources
     */
    private static function isDefaultSources(array $sources): bool
    {
        return collect($sources)->sort()->values()->all() === collect(GearCatalog::DEFAULT_SOURCE_TYPES)->sort()->values()->all();
    }

    /**
     * @param  Profile  $profile
     */
    private static function secondarySpread(array $profile): string
    {
        if ($profile['rated'] === 0) {
            return '';
        }

        $shares = collect(Stat::SECONDARY_KEYS)->mapWithKeys(fn (string $stat): array => [$stat => GearStatistics::share($profile, $stat)])->sort();
        $percent = fn (float $share): string => round($share * 100).'%';

        return GearStatistics::STAT_NAMES[$shares->keys()->last()].' is the most common secondary at '.$percent($shares->last())
            .', '.GearStatistics::STAT_NAMES[$shares->keys()->first()].' the rarest at '.$percent($shares->first()).'.';
    }

    /**
     * Crawlers can't fall back on a missing image, so the zamimg URL must be one that exists.
     */
    private static function icon(?string $icon): string
    {
        return $icon === null ? asset('logo.jpg') : "https://wow.zamimg.com/images/wow/icons/large/{$icon}.jpg";
    }
}
