@php($recordStatus = $record->status())
<span class="tag {{ $recordStatus->chip() }}">{{ $recordStatus->label() }}</span>
