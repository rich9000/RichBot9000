<div class="container mt-5 " style="background-color: aliceblue">
    <h2>Upload Files</h2>

    <div class="container mt-5">
        <h2>File Structure</h2>
        <form action="/chat/upload-file" method="POST">
            @csrf
            <ul class="list-group" id="file-structure">
                @foreach($directoryTree as $name => $item)

                    @include('chat._file_item', ['id' => Str::uuid(), 'name' => $name, 'item' => $item])
                @endforeach
            </ul>
            <button type="submit" class="btn btn-primary mt-3">Submit Selected Files</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('.toggle').on('click', function() {
                var icon = $(this).find('i');
                var target = $(this).data('target');
                $(target).collapse('toggle');

                if (icon.hasClass('fa-plus')) {
                    icon.removeClass('fa-plus').addClass('fa-minus');
                } else {
                    icon.removeClass('fa-minus').addClass('fa-plus');
                }
            });
        });
    </script>

</div>
