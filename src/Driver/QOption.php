<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * Builds the values of the -q (CLI) / qopt (REST) option, shared by both drivers.
 *
 * @internal
 */
final class QOption
{
    /**
     * Each returned value is a separate -q option / qopt field, as vr_wsapi does:
     * the numeric quality, the chained letters, then @<sad-model>, which cannot
     * be combined with the others.
     *
     * Letters come before the multilingual value, which may end with a number.
     *
     * @return list<string>
     */
    public static function values(Request $request): array
    {
        $number = $letters = $multilingual = $model = '';

        foreach ($request->parameters as $name => $value) {
            $param = $request->parameterDefinitions?->findByName($name);
            if ($param === null || $param->cliOption !== '-q') {
                continue;
            }
            if ($param->type === Parameter::TYPE_FLAG) {
                $letters .= $value ? (string) $param->flagValue : '';
                continue;
            }
            $value = (string) $value;
            if (str_starts_with($value, '@')) {
                $model = $value;
            } elseif (is_numeric($value)) {
                $number = $value;
            } else {
                $multilingual .= $value;
            }
        }

        return array_values(array_filter(
            [$number, $letters . $multilingual, $model],
            static fn (string $v): bool => $v !== '',
        ));
    }
}
