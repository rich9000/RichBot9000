<li class="list-group-item">
    @if(isset($item['children']) && count($item['children']) > 0)
        <span class="toggle" data-toggle="collapse" data-target="#folder-{{ $id }}">
            <i class="fas fa-plus"></i>
        </span>
        <strong>{{ $name }}</strong>
        <ul class="list-group collapse" id="folder-{{ $id }}">
            @foreach($item['children'] as $childName => $childItem)
                @include('chat._file_item', ['id' => Str::uuid(), 'name' => $childName, 'item' => $childItem])
            @endforeach
        </ul>
    @else
        <input type="checkbox" name="files[]" value="{{ $item['path'] }}">
        {{ $name }}
    @endif
</li>
