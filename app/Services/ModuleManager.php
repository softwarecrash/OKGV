<?php

namespace App\Services;

use App\Enums\FeatureModule;
use LogicException;

final class ModuleManager
{
    public function enabled(FeatureModule|string $module): bool
    {
        return $this->resolve($module)->enabled();
    }

    public function ensureValidConfiguration(): void
    {
        foreach (FeatureModule::cases() as $module) {
            if (! $module->enabled()) {
                continue;
            }

            foreach ($module->dependencies() as $dependency) {
                if (! $dependency->enabled()) {
                    throw new LogicException(sprintf(
                        'Module configuration invalid: %s requires %s.',
                        $module->value,
                        $dependency->value,
                    ));
                }
            }
        }
    }

    private function resolve(FeatureModule|string $module): FeatureModule
    {
        return $module instanceof FeatureModule
            ? $module
            : FeatureModule::from($module);
    }
}
