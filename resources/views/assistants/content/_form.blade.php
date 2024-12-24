<?php

use App\Services\OpenAIAssistant;
use App\Models\AssistantFunction;

// Fetch the assistant functions from the database
$functions = AssistantFunction::all();


$gpt = new OpenAIAssistant();
$onlineFiles = [];

?>
<div class="container mt-5 " style="background-color: antiquewhite">
    <h2>Create GPT Assistant</h2>

    <form action="" method="POST">

        <div class="form-group">
            <label for="name">Assistant Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" class="form-control" id="model" name="model" value="gpt-3.5-turbo" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" class="form-control" id="description" name="description" required>
        </div>
        <div class="form-group">
            <label for="instructions">Instructions</label>
            <textarea class="form-control" id="instructions" name="instructions" rows="4" required></textarea>
        </div>


        <div class="form-group">
            <label for="files">Select Files</label>
            <div>
                <h5>Available Files</h5>
                @foreach($onlineFiles as $file)
                    <div class="form-check">
                        @dump($file)
                        <input class="form-check-input" type="checkbox" name="onlineFiles[]" value="{{ $file['id'] }}"
                               id="file_{{ $file['id'] }}">
                        <label class="form-check-label"
                               for="file_{{ $file['id'] }}">{{ $file['filename'] }} {{ $file['id'] }}</label>
                    </div>
                @endforeach
            </div>
        </div>


        <div class="form-group">
            <label class="checkbox-inline"><input type="checkbox" name="json_only" value="1"> Generate JSON Only</label>
        </div>

        <div class="form-group mb-3">
            <label for="functions">Select Functions</label>
            <div>
                <h5>Available Functions</h5>
                @foreach($functions as $function)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="functions[]" value="{{ $function->name }}"
                               id="{{ $function->name }}">
                        <label class="form-check-label" for="{{ $function->name }}">{{ $function->description }}</label>
                    </div>
                @endforeach
            </div>
        </div>




        <button type="submit" class="btn btn-primary">Create Assistant</button>
    </form>
</div>


