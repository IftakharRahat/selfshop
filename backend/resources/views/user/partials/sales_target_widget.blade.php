@if(isset($activeSalesTarget) && $activeSalesTarget && isset($salesTargetProgress) && is_array($salesTargetProgress))
    @php
        $isAmountTarget = $activeSalesTarget->target_type === 'amount';
        $targetUnit = $isAmountTarget ? 'TK' : 'Qty';
        $decimalPlaces = $isAmountTarget ? 2 : 0;
        $targetValueText = number_format((float) ($salesTargetProgress['target'] ?? 0), $decimalPlaces) . ' ' . $targetUnit;
        $achievedValueText = number_format((float) ($salesTargetProgress['achieved'] ?? 0), $decimalPlaces) . ' ' . $targetUnit;
        $remainingValueText = number_format((float) ($salesTargetProgress['remaining'] ?? 0), $decimalPlaces) . ' ' . $targetUnit;
        $progressPercent = max(0, min(100, (float) ($salesTargetProgress['progress_percent'] ?? 0)));
    @endphp

    <div class="target-widget">
        <h6>System</h6>
        <ul>
            <li>Sales target achievement system.</li>
            <li>Complete product sales target to qualify.</li>
            <li>Eligible users receive bonus, gift, or reward.</li>
        </ul>

        <div class="small mt-2"><strong>{{ $activeSalesTarget->title }}</strong></div>
        <div class="small">Target Type: {{ ucfirst($activeSalesTarget->target_type) }}</div>
        <div class="small">Target Value: {{ $targetValueText }}</div>
        <div class="small">Order Scope: {{ $activeSalesTarget->order_scope === 'delivered' ? 'Delivered only' : 'Non canceled' }}</div>
        <div class="small">Reward: {{ ucfirst($activeSalesTarget->reward_type) }}{{ $activeSalesTarget->reward_note ? ' - ' . $activeSalesTarget->reward_note : '' }}</div>

        <div class="target-progress">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: {{ $progressPercent }}%;" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="target-metrics">
                <span>Achieved: {{ $achievedValueText }}</span>
                <span>Remaining: {{ $remainingValueText }}</span>
            </div>
            @if(!empty($salesTargetProgress['completed']))
                <span class="target-badge-done">Target Completed</span>
            @endif
        </div>
    </div>
@endif
