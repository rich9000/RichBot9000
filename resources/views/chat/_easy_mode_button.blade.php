<div class="easy-mode">
    <button class="btn btn-primary easy-mode-btn">EasyMode</button>
    <div class="easy-mode-prompt" style="display: none;">
        <form id="easy-mode-form">
            <textarea id="prompt" name="prompt" rows="3" class="form-control"></textarea>
            <div class="audio-recorder" data-target-id="prompt">
                <button type="button" class="btn btn-primary record-btn">
                    <i class="fas fa-microphone"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-success">Submit</button>
        </form>
        <div class="loading-spinner" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
        <div class="easy-mode-result" style="display: none;">
            <p class="result-message"></p>
            <button class="btn btn-primary refresh-btn">Refresh Page</button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Get the CSRF token from the meta tag
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        $('.easy-mode-btn').on('click', function() {
            $('.easy-mode-prompt').toggle();
        });

        $('#easy-mode-form').on('submit', function(e) {
            e.preventDefault();
            $('.easy-mode-prompt').fadeTo('slow', 0.5);
            $('.loading-spinner').show();

            $.ajax({
                url: "{{ route('chat.easy-mode') }}",
                method: 'POST',
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    $('.easy-mode-prompt').fadeTo('slow', 1);
                    $('.loading-spinner').hide();
                    //$('.easy-mode-result .result-message').text(response.map(message => message.content).join("\n"));


                    // Get the text value from the first message's first content item
                    if (response.length > 0 && response[0].content[0].type === 'text') {
                        let extractedText = response[0].content[0].text.value;
                        $('.easy-mode-result .result-message').text(extractedText);
                    } else {
                        $('.easy-mode-result .result-message').text('No text content found.');
                    }

                    $('.easy-mode-result').show();






                    //$('.easy-mode-result').show();
                },
                error: function() {
                    $('.easy-mode-prompt').fadeTo('slow', 1);
                    $('.loading-spinner').hide();
                    alert('An error occurred. Please try again.');
                }
            });
        });

        $('.refresh-btn').on('click', function() {
            location.reload();
        });

        $('.record-btn').on('click', function() {
            var targetId = $(this).parent().data('target-id');
            // Implement your audio recording logic here
            alert('Microphone button clicked for ' + targetId);
        });
    });
</script>

