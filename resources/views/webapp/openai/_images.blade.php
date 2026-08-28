<style>
    .image-api-container { max-width: 900px; margin: 0 auto; }
    .image-api-tabs { display: flex; border-bottom: 1px solid #ccc; margin-bottom: 1em; }
    .image-api-tab { padding: 10px 20px; cursor: pointer; border: none; background: none; font-weight: bold; }
    .image-api-tab.active { border-bottom: 2px solid #007bff; color: #007bff; }
    .image-api-form { display: none; }
    .image-api-form.active { display: block; }
    .image-api-output { margin-top: 2em; }
    .image-api-img { max-width: 100%; border-radius: 8px; box-shadow: 0 2px 8px #0001; margin: 10px 0; }
    .image-api-error { color: #b00; margin-top: 1em; }
    .image-api-label { font-weight: 500; margin-top: 1em; }
    .image-api-input, .image-api-textarea, .image-api-select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; }
    .image-api-btn { margin-top: 1em; padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .image-api-btn:disabled { background: #aaa; }
</style>

<div class="image-api-container card p-4">
    <div class="image-api-tabs">
        <button class="image-api-tab active" data-tab="generate">Generate</button>
        <button class="image-api-tab" data-tab="edit">Edit</button>
        <button class="image-api-tab" data-tab="variation">Variation</button>
        <button class="image-api-tab" data-tab="browse">Browse</button>
    </div>
    <div class="image-api-form active" id="image-api-generate-form">
        <label class="image-api-label">Prompt</label>
        <textarea class="image-api-textarea" id="generate-prompt" rows="2" placeholder="Describe your image..."></textarea>
        <label class="image-api-label">Number of Images</label>
        <input class="image-api-input" id="generate-n" type="number" min="1" max="10" value="1" />
        <label class="image-api-label">Size</label>
        <select class="image-api-select" id="generate-size">
            <option value="1024x1024">1024x1024</option>
            <option value="512x512">512x512</option>
            <option value="256x256">256x256</option>
            <option value="1024x1792">1024x1792</option>
            <option value="1792x1024">1792x1024</option>
        </select>
        <label class="image-api-label">Quality</label>
        <select class="image-api-select" id="generate-quality">
            <option value="standard">Standard</option>
            <option value="hd">HD</option>
        </select>
        <label class="image-api-label">Style</label>
        <select class="image-api-select" id="generate-style">
            <option value="vivid">Vivid</option>
            <option value="natural">Natural</option>
        </select>
        <button class="image-api-btn" id="generate-submit">Generate Image</button>
    </div>
    <div class="image-api-form" id="image-api-edit-form">
        <label class="image-api-label">Image</label>
        <input class="image-api-input" id="edit-image" type="file" accept="image/*" />
        <label class="image-api-label">Prompt</label>
        <textarea class="image-api-textarea" id="edit-prompt" rows="2" placeholder="Describe your edit..."></textarea>
        <label class="image-api-label">Mask (optional)</label>
        <input class="image-api-input" id="edit-mask" type="file" accept="image/*" />
        <label class="image-api-label">Number of Images</label>
        <input class="image-api-input" id="edit-n" type="number" min="1" max="10" value="1" />
        <label class="image-api-label">Size</label>
        <select class="image-api-select" id="edit-size">
            <option value="1024x1024">1024x1024</option>
            <option value="512x512">512x512</option>
            <option value="256x256">256x256</option>
        </select>
        <button class="image-api-btn" id="edit-submit">Edit Image</button>
    </div>
    <div class="image-api-form" id="image-api-variation-form">
        <label class="image-api-label">Image</label>
        <input class="image-api-input" id="variation-image" type="file" accept="image/*" />
        <label class="image-api-label">Number of Variations</label>
        <input class="image-api-input" id="variation-n" type="number" min="1" max="10" value="1" />
        <label class="image-api-label">Size</label>
        <select class="image-api-select" id="variation-size">
            <option value="1024x1024">1024x1024</option>
            <option value="512x512">512x512</option>
            <option value="256x256">256x256</option>
        </select>
        <button class="image-api-btn" id="variation-submit">Create Variation</button>
    </div>
    <div class="image-api-form" id="image-api-browse-form">
        <label class="image-api-label">Browse Saved Images</label>
        <div id="browse-folder-select" style="margin-bottom: 1em;"></div>
        <div id="browse-images-list" style="display: flex; flex-wrap: wrap; gap: 16px;"></div>
        <div id="browse-image-preview" style="margin-top: 1em;"></div>
    </div>
    <div class="image-api-output" id="image-api-output"></div>
    <div class="image-api-error" id="image-api-error"></div>
</div>

<script>
class OpenAIImageUI {
    constructor() {
        this.apiBase = '/api/openai/images';
        this.token = appState.apiToken;
        this.output = document.getElementById('image-api-output');
        this.error = document.getElementById('image-api-error');
        this.defaultFolder = '/';
        this.browseFolders = [this.defaultFolder];
        this.initTabs();
        this.initForms();
        this.initBrowse();
    }
    initTabs() {
        const tabs = document.querySelectorAll('.image-api-tab');
        const forms = document.querySelectorAll('.image-api-form');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                forms.forEach(f => f.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('image-api-' + tab.dataset.tab + '-form').classList.add('active');
                this.clearOutput();
                if (tab.dataset.tab === 'browse') {
                    this.loadBrowseFolders();
                    this.loadBrowseImages(this.defaultFolder);
                }
            });
        });
    }
    initForms() {
        document.getElementById('generate-submit').onclick = () => this.handleGenerate();
        document.getElementById('edit-submit').onclick = () => this.handleEdit();
        document.getElementById('variation-submit').onclick = () => this.handleVariation();
    }
    initBrowse() {
        // Folder select (if you want to support more folders in the future)
        const selectDiv = document.getElementById('browse-folder-select');
        selectDiv.innerHTML = '';
        if (this.browseFolders.length > 1) {
            const select = document.createElement('select');
            select.className = 'image-api-select';
            this.browseFolders.forEach(folder => {
                const opt = document.createElement('option');
                opt.value = folder;
                opt.textContent = folder;
                select.appendChild(opt);
            });
            select.onchange = () => this.loadBrowseImages(select.value);
            selectDiv.appendChild(select);
        } else {
            selectDiv.textContent = this.defaultFolder;
        }
    }
    loadBrowseFolders() {
        // If you want to support dynamic folder listing, call the API here
        // For now, just use this.browseFolders
        this.initBrowse();
    }
    async loadBrowseImages(folder) {
        const listDiv = document.getElementById('browse-images-list');
        const previewDiv = document.getElementById('browse-image-preview');
        listDiv.innerHTML = '<div>Loading...</div>';
        previewDiv.innerHTML = '';
        try {
            const res = await fetch('/api/list/tree', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + this.token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ directory: folder, context: 'image_generator' })
            });
            const data = await res.json();
            if (!res.ok || !data.data || !data.data.files) throw new Error(data.message || 'Failed to list files');
            const files = data.data.files.filter(f => f.match(/\.(png|jpg|jpeg|gif|webp)$/i));
            if (!files.length) {
                listDiv.innerHTML = '<div>No images found in this folder.</div>';
                return;
            }
            listDiv.innerHTML = '';
            files.forEach(file => {
                const img = document.createElement('img');
                img.src = '/storage/' + file.replace(/^public\//, '');
                img.className = 'image-api-img';
                img.style.maxWidth = '120px';
                img.style.cursor = 'pointer';
                img.title = file;
                img.onclick = () => {
                    previewDiv.innerHTML = `<img src="${img.src}" style="max-width: 100%; border-radius: 8px;" /><div style='word-break:break-all; margin-top:0.5em;'><code>${file}</code></div>`;
                };
                listDiv.appendChild(img);
            });
        } catch (e) {
            listDiv.innerHTML = '<div style="color:#b00">' + e.message + '</div>';
        }
    }
    clearOutput() {
        this.output.innerHTML = '';
        this.error.textContent = '';
    }
    setLoading(loading) {
        document.querySelectorAll('.image-api-btn').forEach(btn => btn.disabled = loading);
    }
    async handleGenerate() {
        this.clearOutput();
        this.setLoading(true);
        const prompt = document.getElementById('generate-prompt').value;
        const n = parseInt(document.getElementById('generate-n').value) || 1;
        const size = document.getElementById('generate-size').value;
        const quality = document.getElementById('generate-quality').value;
        const style = document.getElementById('generate-style').value;
        try {
            const res = await fetch(this.apiBase + '/generate', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + this.token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ prompt, n, size, quality, style })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to generate image');
            this.displayImages(data.images);
        } catch (e) {
            this.error.textContent = e.message;
        } finally {
            this.setLoading(false);
        }
    }
    async handleEdit() {
        this.clearOutput();
        this.setLoading(true);
        const imageInput = document.getElementById('edit-image');
        const maskInput = document.getElementById('edit-mask');
        const prompt = document.getElementById('edit-prompt').value;
        const n = parseInt(document.getElementById('edit-n').value) || 1;
        const size = document.getElementById('edit-size').value;
        if (!imageInput.files[0]) {
            this.error.textContent = 'Please select an image to edit.';
            this.setLoading(false);
            return;
        }
        const formData = new FormData();
        formData.append('image', imageInput.files[0]);
        if (maskInput.files[0]) formData.append('mask', maskInput.files[0]);
        formData.append('prompt', prompt);
        formData.append('n', n);
        formData.append('size', size);
        try {
            const res = await fetch(this.apiBase + '/edit', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + this.token,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to edit image');
            this.displayImages(data.images);
        } catch (e) {
            this.error.textContent = e.message;
        } finally {
            this.setLoading(false);
        }
    }
    async handleVariation() {
        this.clearOutput();
        this.setLoading(true);
        const imageInput = document.getElementById('variation-image');
        const n = parseInt(document.getElementById('variation-n').value) || 1;
        const size = document.getElementById('variation-size').value;
        if (!imageInput.files[0]) {
            this.error.textContent = 'Please select an image for variation.';
            this.setLoading(false);
            return;
        }
        const formData = new FormData();
        formData.append('image', imageInput.files[0]);
        formData.append('n', n);
        formData.append('size', size);
        try {
            const res = await fetch(this.apiBase + '/variation', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + this.token,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to create variation');
            this.displayImages(data.images);
        } catch (e) {
            this.error.textContent = e.message;
        } finally {
            this.setLoading(false);
        }
    }
    displayImages(images) {
        if (!images || !images.length) {
            this.output.innerHTML = '<div>No images returned.</div>';
            return;
        }
        this.output.innerHTML = images.map(url => `<img class="image-api-img" src="${url}" alt="Generated image" />`).join('');
    }
}

new OpenAIImageUI();
</script>
