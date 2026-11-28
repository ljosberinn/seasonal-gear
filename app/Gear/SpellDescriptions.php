<?php

namespace App\Gear;

/**
 * Fills in the `$` variables of spell descriptions (`Spell.Description_lang`) the way the game's
 * tooltips do, e.g. `$s1`, `$17351d`, `${$h/3}.1` or `$?s2060[Greater Heal][Healing Wave]`.
 */
class SpellDescriptions
{
    /** `$@spelldesc` can nest; Wowhead stops at a few levels too. */
    private const int MAX_DEPTH = 3;

    /** @var array<int, string> by spell ID */
    private array $descriptions = [];

    /** @var array<int, array<int, array{points: float, variance: float, period: float, radius: int, chainTargets: int, chainAmplitude: float, pointsPerResource: float, amplitude: float}>> by spell ID, then effect index */
    private array $effects = [];

    /** @var array<int, array{duration: int, range: int}> by spell ID */
    private array $misc = [];

    /** @var array<int, array{chance: int, charges: int, stacks: int, cooldown: int}> by spell ID */
    private array $auraOptions = [];

    /** @var array<int, int> by spell ID */
    private array $maxTargets = [];

    /** @var array<int, int> milliseconds by `SpellDuration` ID */
    private array $durations = [];

    /** @var array<int, float> yards by `SpellRadius` ID */
    private array $radii = [];

    /** @var array<int, float> yards by `SpellRange` ID */
    private array $ranges = [];

    /** @var array<int, array<string, string>> variable definitions by spell ID */
    private array $variables = [];

    /** @var array<int, true> spells whose description used a variable that could not be filled in */
    private array $unresolved = [];

    public function __construct(Db2Directory $db2)
    {
        foreach ($db2->rows('Spell') as $row) {
            if ($row['Description_lang'] !== '') {
                $this->descriptions[(int) $row['ID']] = $row['Description_lang'];
            }
        }

        foreach ($db2->rows('SpellEffect') as $row) {
            if ($row['DifficultyID'] === '0') {
                $this->effects[(int) $row['SpellID']][(int) $row['EffectIndex']] = [
                    'points' => (float) $row['EffectBasePointsF'],
                    'variance' => (float) $row['Variance'],
                    'period' => (float) $row['EffectAuraPeriod'],
                    'radius' => (int) ($row['EffectRadiusIndex_0'] ?: $row['EffectRadiusIndex_1']),
                    'chainTargets' => (int) $row['EffectChainTargets'],
                    'chainAmplitude' => (float) $row['EffectChainAmplitude'],
                    'pointsPerResource' => (float) $row['EffectPointsPerResource'],
                    'amplitude' => (float) $row['EffectAmplitude'],
                ];
            }
        }

        foreach ($db2->rows('SpellMisc') as $row) {
            if ($row['DifficultyID'] === '0') {
                $this->misc[(int) $row['SpellID']] = ['duration' => (int) $row['DurationIndex'], 'range' => (int) $row['RangeIndex']];
            }
        }

        foreach ($db2->rows('SpellAuraOptions') as $row) {
            if ($row['DifficultyID'] === '0') {
                $this->auraOptions[(int) $row['SpellID']] = [
                    'chance' => (int) $row['ProcChance'],
                    'charges' => (int) $row['ProcCharges'],
                    'stacks' => (int) $row['CumulativeAura'],
                    'cooldown' => (int) $row['ProcCategoryRecovery'],
                ];
            }
        }

        foreach ($db2->rows('SpellTargetRestrictions') as $row) {
            if ($row['DifficultyID'] === '0') {
                $this->maxTargets[(int) $row['SpellID']] = (int) $row['MaxTargets'];
            }
        }

        $this->durations = array_map(intval(...), array_column(iterator_to_array($db2->rows('SpellDuration'), false), 'Duration', 'ID'));
        $this->radii = array_map(floatval(...), array_column(iterator_to_array($db2->rows('SpellRadius'), false), 'Radius', 'ID'));
        $this->ranges = array_map(floatval(...), array_column(iterator_to_array($db2->rows('SpellRange'), false), 'RangeMax_0', 'ID'));

        $definitions = array_column(iterator_to_array($db2->rows('SpellDescriptionVariables'), false), 'Variables', 'ID');

        foreach ($db2->rows('SpellXDescriptionVariables') as $row) {
            preg_match_all('/\$(\w+)=(.*)/', $definitions[$row['SpellDescriptionVariablesID']] ?? '', $matches, PREG_SET_ORDER);

            foreach ($matches as [, $name, $value]) {
                $this->variables[(int) $row['SpellID']][$name] = trim($value);
            }
        }
    }

    /**
     * The description on one line, or null when the spell has none.
     */
    public function render(int $spellId, int $depth = 0): ?string
    {
        $description = $this->descriptions[$spellId] ?? null;

        if ($description === null || $depth > self::MAX_DEPTH) {
            return null;
        }

        $text = preg_replace('/\$@spellicon\d+/', '', $description);
        $text = preg_replace_callback('/\$@spelldesc(\d+)/', fn (array $match): string => $this->render((int) $match[1], $depth + 1) ?? '', $text);
        $text = $this->resolveConditionals($text);
        $text = preg_replace_callback('/\$\{([^{}]*)\}(?:\.(\d))?/', function (array $match) use ($spellId): string {
            $value = $this->evaluate($spellId, $match[1]);

            return $value === null ? $this->unresolved($spellId) : number_format($value, (int) ($match[2] ?? 0), '.', '');
        }, $text);
        $text = preg_replace_callback('/\$([\/*])(\d+);\$?(\d*)([a-zA-Z])(\d?)/', function (array $match) use ($spellId): string {
            $value = $this->value($match[3] === '' ? $spellId : (int) $match[3], $match[4], (int) ($match[5] ?: 1));

            if ($value === null) {
                return $this->unresolved($spellId);
            }

            return $this->formatNumber($match[1] === '/' ? abs($value) / (int) $match[2] : abs($value) * (int) $match[2]);
        }, $text);
        $text = preg_replace_callback('/\$<(\w+)>/', function (array $match) use ($spellId): string {
            $value = $this->variable($spellId, $match[1]);

            return $value === null ? $this->unresolved($spellId) : $this->formatNumber($value);
        }, $text);
        // `$g` and `$l` pick words rather than numbers, so they are left for below.
        $text = preg_replace_callback('/\$(\d*)(proccooldown|(?![gl][^:;]*:)[a-zA-Z])(\d?)/', function (array $match) use ($spellId): string {
            $id = $match[1] === '' ? $spellId : (int) $match[1];
            $index = (int) ($match[3] ?: 1);
            $value = $this->value($id, $match[2], $index);

            return match (true) {
                $value === null => $this->unresolved($spellId),
                strtolower($match[2]) === 'd' => $this->formatDuration($value),
                in_array(strtolower($match[2]), ['s', 'm'], true) => $this->formatPoints($id, $index),
                default => $this->formatNumber(abs($value)),
            };
        }, $text);
        $text = preg_replace('/\$g([^:;]*):[^;]*;/', '$1', $text);
        $text = $this->resolvePlurals($text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Milliseconds before the spell's proc can trigger again.
     */
    public function procCooldown(int $spellId): int
    {
        return $this->auraOptions[$spellId]['cooldown'] ?? 0;
    }

    public function mentionsProcCooldown(int $spellId): bool
    {
        return str_contains($this->descriptions[$spellId] ?? '', '$proccooldown');
    }

    /**
     * Spells whose description had a variable that could not be filled in, for the import to report.
     *
     * @return list<int>
     */
    public function unresolvedSpells(): array
    {
        $spellIds = array_keys($this->unresolved);
        sort($spellIds);

        return $spellIds;
    }

    /**
     * Raw value of a description variable such as `s1` (`$s1`), with durations in seconds.
     */
    private function value(int $spellId, string $token, int $index): ?float
    {
        // Like Wowhead, an index the spell lacks reads its first effect, e.g. Battleground Gladiator gear's `$s2`.
        $effect = $this->effects[$spellId][$index - 1] ?? $this->effects[$spellId][0] ?? null;
        $auraOptions = $this->auraOptions[$spellId] ?? null;

        return match (strtolower($token)) {
            's', 'm', 'w' => $effect['points'] ?? null,
            'o' => $effect !== null && $effect['period'] > 0 && $this->duration($spellId) !== null
                ? $effect['points'] * $this->duration($spellId) * 1000 / $effect['period']
                : null,
            't' => $effect !== null && $effect['period'] > 0 ? $effect['period'] / 1000 : null,
            'a' => $effect !== null ? $this->radii[$effect['radius']] ?? 0.0 : null,
            'x' => $effect['chainTargets'] ?? null,
            'f' => $effect['chainAmplitude'] ?? null,
            'b' => $effect['pointsPerResource'] ?? null,
            'e' => $effect['amplitude'] ?? null,
            'd' => $this->duration($spellId),
            'r' => $this->ranges[$this->misc[$spellId]['range'] ?? 0] ?? null,
            'i' => isset($this->maxTargets[$spellId]) ? (float) $this->maxTargets[$spellId] : null,
            'h' => $auraOptions !== null ? (float) $auraOptions['chance'] : null,
            'n' => $auraOptions !== null ? (float) $auraOptions['charges'] : null,
            'u' => $auraOptions !== null ? (float) $auraOptions['stacks'] : null,
            'proccooldown' => $auraOptions !== null ? $auraOptions['cooldown'] / 1000 : null,
            default => null,
        };
    }

    /**
     * Seconds, or null for spells without a duration.
     */
    private function duration(int $spellId): ?float
    {
        $milliseconds = $this->durations[$this->misc[$spellId]['duration'] ?? 0] ?? null;

        return $milliseconds === null || $milliseconds <= 0 ? null : $milliseconds / 1000;
    }

    private function variable(int $spellId, string $name): ?float
    {
        $definition = $this->variables[$spellId][$name] ?? $this->variables[$spellId]["{$name}1"] ?? null;

        if ($definition === null) {
            return null;
        }

        $expression = preg_replace('/^\$\{(.*)\}$/', '$1', $this->resolveConditionals($definition));

        return $this->evaluate($spellId, preg_replace('/\$\{([^{}]*)\}/', '($1)', $expression));
    }

    /**
     * Conditions such as `$?s2060` test the reader's spells and auras, so the fallback branch is
     * the one that applies to everyone.
     */
    private function resolveConditionals(string $text): string
    {
        return preg_replace('/\$\?(?:!?\(?[a-zA-Z]+\d+(?:[&|]!?[a-zA-Z]+\d+)*\)?\[[^\[\]]*\]\??)+\[([^\[\]]*)\]/', '$1', $text);
    }

    /**
     * `$lsingular:plural;` follows the number written before it.
     */
    private function resolvePlurals(string $text): string
    {
        while (preg_match('/\$l([^:;]*):([^;]*);/', $text, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $isSingular = preg_match('/(\d+(?:\.\d+)?)\D*$/', substr($text, 0, $offset), $number) && (float) $number[1] === 1.0;
            $text = substr_replace($text, $isSingular ? $match[1][0] : $match[2][0], $offset, strlen($match[0][0]));
        }

        return $text;
    }

    /**
     * Arithmetic over description variables, e.g. `$m1/-1000` or `($s1/5)*$d`.
     */
    private function evaluate(int $spellId, string $expression): ?float
    {
        $failed = false;
        $arithmetic = preg_replace_callback('/\$(?:<(\w+)>|(\d*)(proccooldown|[a-zA-Z])(\d?))/', function (array $match) use ($spellId, &$failed): string {
            $value = $match[1] !== ''
                ? $this->variable($spellId, $match[1])
                : $this->value($match[2] === '' ? $spellId : (int) $match[2], $match[3], (int) ($match[4] ?: 1));
            $failed = $failed || $value === null;

            return '('.($value ?? 0).')';
        }, $expression);

        if ($failed) {
            return null;
        }

        $tokens = preg_split('/\s*([-+*\/()])\s*/', trim($arithmetic), flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $position = 0;
        $value = $this->parseSum($tokens, $position);

        return $position === count($tokens) ? $value : null;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function parseSum(array $tokens, int &$position): ?float
    {
        $value = $this->parseProduct($tokens, $position);

        while ($value !== null && in_array($tokens[$position] ?? null, ['+', '-'], true)) {
            $operator = $tokens[$position++];
            $operand = $this->parseProduct($tokens, $position);
            $value = $operand === null ? null : ($operator === '+' ? $value + $operand : $value - $operand);
        }

        return $value;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function parseProduct(array $tokens, int &$position): ?float
    {
        $value = $this->parseFactor($tokens, $position);

        while ($value !== null && in_array($tokens[$position] ?? null, ['*', '/'], true)) {
            $operator = $tokens[$position++];
            $operand = $this->parseFactor($tokens, $position);
            $value = match (true) {
                $operand === null, $operator === '/' && $operand == 0 => null,
                $operator === '*' => $value * $operand,
                default => $value / $operand,
            };
        }

        return $value;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function parseFactor(array $tokens, int &$position): ?float
    {
        $token = $tokens[$position++] ?? null;

        if ($token === '-') {
            $value = $this->parseFactor($tokens, $position);

            return $value === null ? null : -$value;
        }

        if ($token === '(') {
            $value = $this->parseSum($tokens, $position);

            return ($tokens[$position++] ?? null) === ')' ? $value : null;
        }

        return $token !== null && is_numeric($token) ? (float) $token : null;
    }

    /**
     * Effect points, as a range when the effect rolls between two values (`SpellEffect.Variance`).
     */
    private function formatPoints(int $spellId, int $index): string
    {
        $effect = $this->effects[$spellId][$index - 1] ?? $this->effects[$spellId][0];
        $points = abs($effect['points']);

        if ($effect['variance'] <= 0) {
            return $this->formatNumber($points);
        }

        $spread = $points * $effect['variance'] / 2;

        // Variance is a float32, so e.g. 38.5 ± 18.18% lands just below 31.5; the inner round undoes that.
        return round(round($points - $spread, 3)).' to '.round(round($points + $spread, 3));
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function formatDuration(float $seconds): string
    {
        $whole = (int) $seconds;

        return match (true) {
            $whole != $seconds => $this->formatNumber($seconds).' sec',
            $whole >= 86400 && $whole % 86400 === 0 => ($whole / 86400).' '.($whole === 86400 ? 'day' : 'days'),
            $whole >= 3600 && $whole % 3600 === 0 => ($whole / 3600).' '.($whole === 3600 ? 'hour' : 'hrs'),
            $whole >= 60 && $whole % 60 === 0 => ($whole / 60).' min',
            default => "{$whole} sec",
        };
    }

    private function unresolved(int $spellId): string
    {
        $this->unresolved[$spellId] = true;

        return '?';
    }
}
