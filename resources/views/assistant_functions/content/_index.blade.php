<div id="functions_content_section">
    <div id="functions_create_section">
        <a class="nav-content-loader btn btn-primary mb-3"
           onclick="
           loadContent(appState.tokens.richbot,`/api/content/assistant_functions.content._form`, 'targetCreateFunctionDiv');
           "


           href="#" data-section="assistant_functions.content._form" data-target="functions_create_section">Create New Function</a>
    </div>
    <div id="targetCreateFunctionDiv">

    </div>


    @include('assistant_functions.content._datatable')

</div>
