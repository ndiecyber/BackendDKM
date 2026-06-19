<?php

namespace App\Services\Qurban;

use App\Models\Qurban\AnimalGroup;
use App\Models\Qurban\QurbanPeriod;

class GroupingService
{
    /**
     * Auto-assign a shohibul to an animal group based on target_type.
     *
     * For kambing: returns (or creates) the "Kambing Mandiri" group.
     * For sapi: finds the first group with < 7 members, or creates a new one.
     */
    public function assignGroup(QurbanPeriod $period, string $targetType): AnimalGroup
    {
        if ($targetType === 'kambing') {
            return $this->assignKambingGroup($period);
        }

        return $this->assignSapiGroup($period);
    }

    private function assignKambingGroup(QurbanPeriod $period): AnimalGroup
    {
        return AnimalGroup::firstOrCreate(
            [
                'period_id' => $period->id,
                'target_type' => 'kambing',
                'name' => 'Kambing Mandiri',
            ]
        );
    }

    private function assignSapiGroup(QurbanPeriod $period): AnimalGroup
    {
        // Find existing sapi groups ordered by name, with member count < 7
        $availableGroup = AnimalGroup::where('period_id', $period->id)
            ->where('target_type', 'sapi')
            ->withCount('shohibuls')
            ->orderBy('name')
            ->get()
            ->first(fn ($group) => $group->shohibuls_count < 7);

        if ($availableGroup) {
            return $availableGroup;
        }

        // All groups full or no groups exist — create a new one
        $currentCount = AnimalGroup::where('period_id', $period->id)
            ->where('target_type', 'sapi')
            ->count();

        return AnimalGroup::create([
            'period_id' => $period->id,
            'target_type' => 'sapi',
            'name' => 'Sapi '.($currentCount + 1),
        ]);
    }
}
