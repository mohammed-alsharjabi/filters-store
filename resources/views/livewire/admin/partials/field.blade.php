<label @class(['full-field'=>$field['type']==='textarea','check-field'=>$field['type']==='checkbox'])>
    <span>{{ $field['label'] }}</span>
    @if($field['type']==='textarea')
        <textarea wire:model.blur="data.{{ $name }}" rows="{{ in_array($name,['body','content','description'])?10:4 }}"></textarea>
    @elseif($field['type']==='checkbox')
        <input type="checkbox" wire:model="data.{{ $name }}">
    @elseif($field['type']==='select')
        <select wire:model.live="data.{{ $name }}"><option value="">— اختر —</option>@foreach($options[$name] as $option)@php($title=isset($option->title)?$option->title:$option->name)<option value="{{ $option->id }}">{{ $title }}</option>@endforeach</select>
    @elseif($field['type']==='select-options')
        <select wire:model.live="data.{{ $name }}">@foreach($field['options'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
    @else
        <input type="{{ $field['type'] }}" @if(isset($field['step'])) step="{{ $field['step'] }}" @endif wire:model.blur="data.{{ $name }}" @if($field['type']==='url') dir="ltr" @endif>
    @endif
    @error('data.'.$name)<small>{{ $message }}</small>@enderror
</label>
