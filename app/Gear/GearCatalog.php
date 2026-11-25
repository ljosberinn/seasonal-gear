<?php

namespace App\Gear;

use RuntimeException;

/**
 * Read side of the dataset written by `gear:import`.
 */
class GearCatalog
{
    public const array SOURCE_TYPES = [
        'raid' => 'Raid',
        'special' => 'Special encounter',
        'tier' => 'Tier set',
        'dungeon' => 'Dungeon',
        'delve' => 'Delves',
        'world' => 'World',
        'catalyst' => 'Catalyst',
        'crafted' => 'Crafted',
    ];

    private const int ITEM_CLASS_WEAPON = 2;

    private const int ARMOR_SUBCLASS_SHIELD = 6;

    private const int INVENTORY_TYPE_HELD_IN_OFF_HAND = 23;

    private const array WEAPON_TYPES = [
        0 => 'Axe', 1 => 'Axe', 2 => 'Bow', 3 => 'Gun', 4 => 'Mace', 5 => 'Mace', 6 => 'Polearm', 7 => 'Sword',
        8 => 'Sword', 9 => 'Warglaive', 10 => 'Staff', 13 => 'Fist Weapon', 15 => 'Dagger', 18 => 'Crossbow', 19 => 'Wand',
    ];

    private const array ARMOR_TYPES = [1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate', 6 => 'Shield'];

    /** @var array{build: string, season: array{slug: string, name: string}, items: list<array<string, mixed>>, sources: list<array<string, mixed>>}|null */
    private ?array $dataset = null;

    public function __construct(private readonly string $path) {}

    /**
     * @return array{build: string, season: array{slug: string, name: string}}
     */
    public function meta(): array
    {
        $dataset = $this->dataset();

        return ['build' => $dataset['build'], 'season' => $dataset['season']];
    }

    /**
     * @return list<array<string, mixed>> items with their `sources`, best matches first
     */
    public function search(GearFilter $filter): array
    {
        $dataset = $this->dataset();
        $sourcesByItem = [];

        foreach ($dataset['sources'] as $source) {
            if ($filter->sources === [] || in_array($source['type'], $filter->sources, true)) {
                $sourcesByItem[$source['itemId']][] = $source;
            }
        }

        $results = [];

        foreach ($dataset['items'] as $item) {
            if (! isset($sourcesByItem[$item['id']]) || ! $this->matches($item, $filter)) {
                continue;
            }

            $results[] = [...$item, 'sources' => $sourcesByItem[$item['id']]];
        }

        usort($results, fn (array $a, array $b): int => [$this->statWeight($b, $filter), $b['itemLevel'] ?? 0, $a['name']]
            <=> [$this->statWeight($a, $filter), $a['itemLevel'] ?? 0, $b['name']]);

        return $results;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function typeLabel(array $item): string
    {
        if ($item['classId'] === self::ITEM_CLASS_WEAPON) {
            return self::WEAPON_TYPES[$item['subclassId']] ?? 'Weapon';
        }

        if ($item['inventoryType'] === self::INVENTORY_TYPE_HELD_IN_OFF_HAND) {
            return 'Held In Off-hand';
        }

        return self::ARMOR_TYPES[$item['subclassId']] ?? '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function matches(array $item, GearFilter $filter): bool
    {
        if ($filter->slot !== null && $item['slot'] !== $filter->slot->value) {
            return false;
        }

        // Crafted items take whichever secondaries the crafter picks.
        if (! $item['choiceStats'] && array_diff($filter->stats, array_keys($item['secondaries'])) !== []) {
            return false;
        }

        return $filter->class === null || $this->isUsableBy($item, $filter->class, $filter->specs);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array{id: int, armor: int, specs: array<string, array{primary: string, weapons: list<int>, shield?: bool, off_hand?: bool}>}  $class
     * @param  list<string>  $specKeys
     */
    private function isUsableBy(array $item, array $class, array $specKeys): bool
    {
        $allowableClass = $item['allowableClass'];

        if ($allowableClass > 0 && ($allowableClass & (1 << ($class['id'] - 1))) === 0) {
            return false;
        }

        if (Slot::from($item['slot'])->isArmor() && $item['subclassId'] !== $class['armor']) {
            return false;
        }

        $specs = $specKeys === [] ? $class['specs'] : array_intersect_key($class['specs'], array_flip($specKeys));

        foreach ($specs as $spec) {
            if ($this->isUsableBySpec($item, $spec)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array{primary: string, weapons: list<int>, shield?: bool, off_hand?: bool}  $spec
     */
    private function isUsableBySpec(array $item, array $spec): bool
    {
        if ($item['primaries'] !== [] && ! in_array($spec['primary'], $item['primaries'], true)) {
            return false;
        }

        return match (true) {
            $item['classId'] === self::ITEM_CLASS_WEAPON => in_array($item['subclassId'], $spec['weapons'], true),
            $item['subclassId'] === self::ARMOR_SUBCLASS_SHIELD => $spec['shield'] ?? false,
            $item['inventoryType'] === self::INVENTORY_TYPE_HELD_IN_OFF_HAND => $spec['off_hand'] ?? false,
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function statWeight(array $item, GearFilter $filter): int
    {
        return array_sum(array_intersect_key($item['secondaries'], array_flip($filter->stats)));
    }

    /**
     * @return array{build: string, season: array{slug: string, name: string}, items: list<array<string, mixed>>, sources: list<array<string, mixed>>}
     */
    private function dataset(): array
    {
        if ($this->dataset !== null) {
            return $this->dataset;
        }

        if (! is_file($this->path)) {
            throw new RuntimeException("Gear dataset missing at {$this->path}; run `php artisan gear:import`.");
        }

        return $this->dataset = json_decode((string) file_get_contents($this->path), true, flags: JSON_THROW_ON_ERROR);
    }
}
