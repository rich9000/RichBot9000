class FileDataNode extends DataNode {
    constructor(data = {}) {
        super({ ...data, subtype: 'file' });
        this.type = 'data';
        this.subtype = 'file'; 
        this.name = 'File Data';
        if (!this.content) this.content = {};
        // Store selected files/folders as an array
        this.content.selected = Array.isArray(data.content?.selected) ? data.content.selected : [];
        this.content.currentPath = data.content?.currentPath || '';
    }

    async fetchDirectory(path, context = {}) {
        const dirPath = path || '/';
        const root = (context && context.fileRoot) ? context.fileRoot : 'file_action';
        const token = window.appState?.apiToken || '';

        // Build the URL for /api/list/tree
        let url = `/api/list/tree?context=file_action`;
        if (dirPath && dirPath !== '/') {
            url += `&directory=${encodeURIComponent(dirPath)}`;
        }

        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        });
        if (!response.ok) return [];
        const data = await response.json();
        return data.data?.tree || [];
    }

    // Helper to render the file/folder tree recursively
    renderTree(tree, basePath = '', nodeIndex) {
        if (!Array.isArray(tree)) return '';
        return `<ul class="file-tree-list">${tree.map(item => {
            const fullPath = basePath ? `${basePath}/${item.name}` : item.name;
            if (item.type === 'folder') {
                return `<li class="file-tree-folder">
                    <span class="file-tree-folder-label" data-path="${fullPath}" onclick="window.FileDataNode_onFolderClick('${fullPath}', ${nodeIndex})">
                        <i class="fas fa-folder"></i> ${item.name}
                    </span>
                    ${this.renderTree(item.contents, fullPath, nodeIndex)}
                </li>`;
            } else {
                const isSelected = this.content.selected.includes(fullPath);
                return `<li class="file-tree-file">
                    <span class="file-tree-file-label" data-path="${fullPath}">
                        <i class="fas fa-file"></i> ${item.name}
                    </span>
                    <button class="btn btn-sm btn-${isSelected ? 'danger' : 'primary'} ms-2" type="button" onclick="window.FileDataNode_onToggleSelect('${fullPath}', ${nodeIndex})">
                        ${isSelected ? 'Remove' : 'Add'}
                    </button>
                    <button class="btn btn-sm btn-outline-secondary ms-1" type="button" onclick="window.FileDataNode_onPreview('${fullPath}', ${nodeIndex})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a class="btn btn-sm btn-outline-success ms-1" href="/api/download?context=file_action&file=${encodeURIComponent(fullPath)}" target="_blank">
                        <i class="fas fa-download"></i>
                    </a>
                </li>`;
            }
        }).join('')}</ul>`;
    }

    getNodeInfo(context = {}) {
        if (!this.content.selected || this.content.selected.length === 0) {
            return 'No files or folders selected.';
        }
        return `Selected: <ul>${this.content.selected.map(f => `<li>${f}</li>`).join('')}</ul>`;
    }

    getSettingsFormTemplate(nodeIndex, context = {}) {
        setTimeout(() => {
            if (!window.FileDataNode_onFolderClick) {
                window.FileDataNode_onFolderClick = async (path, idx) => {
                    const node = window.pathBuilder.selectedPath.nodes[idx];
                    node.content.currentPath = path;
                    window.pathBuilder.renderNodes();
                };
            }
            if (!window.FileDataNode_onToggleSelect) {
                window.FileDataNode_onToggleSelect = (path, idx) => {
                    const node = window.pathBuilder.selectedPath.nodes[idx];
                    if (!node.content.selected) node.content.selected = [];
                    const i = node.content.selected.indexOf(path);
                    if (i === -1) node.content.selected.push(path);
                    else node.content.selected.splice(i, 1);
                    window.pathBuilder.renderNodes();
                };
            }
            if (!window.FileDataNode_onPreview) {
                window.FileDataNode_onPreview = async (path, idx) => {
                    const token = window.appState?.apiToken || '';
                    // Always include context=file_action in download
                    const resp = await fetch(`/api/download?context=file_action&file=${encodeURIComponent(path)}`, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    if (!resp.ok) {
                        alert('Failed to load file');
                        return;
                    }
                    const text = await resp.text();
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.innerHTML = `<div class='modal-dialog modal-lg'><div class='modal-content'><div class='modal-header'><h5 class='modal-title'>Preview: ${path}</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body'><pre style="max-height:400px;overflow:auto;">${text.replace(/</g, '&lt;')}</pre></div></div></div>`;
                    document.body.appendChild(modal);
                    if (window.bootstrap && window.bootstrap.Modal) {
                        const bsModal = new window.bootstrap.Modal(modal);
                        bsModal.show();
                        modal.addEventListener('hidden.bs.modal', () => modal.remove());
                    } else {
                        modal.style.display = 'block';
                        setTimeout(() => modal.remove(), 5000);
                    }
                };
            }
            // Load the tree
            (async () => {
                const node = window.pathBuilder.selectedPath.nodes[nodeIndex];
                const root = '';
                const dirPath = node.content.currentPath || root;
                const tree = await node.fetchDirectory(dirPath, context);
                const treeDiv = document.getElementById(`file-tree-${nodeIndex}`);
                if (treeDiv) treeDiv.innerHTML = node.renderTree(tree, dirPath, nodeIndex);
            })();
        }, 0);
        return `
            <div class="settings-grid">
                <div class="settings-row">
                    <div class="settings-label">File/Folder Browser</div>
                    <div class="settings-field">
                        <div id="file-tree-${nodeIndex}">Loading...</div>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Selected</div>
                    <div class="settings-field">
                        <ul>
                            ${(this.content.selected || []).map(f => `<li>${f} <button class='btn btn-sm btn-danger' type='button' onclick='window.FileDataNode_onToggleSelect("${f}", ${nodeIndex})'>Remove</button></li>`).join('')}
                        </ul>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-label">Context Key</div>
                    <div class="settings-field">
                        <input type="text" class="form-control" value="file_action" readonly />
                    </div>
                </div>
            </div>
            <button class="btn btn-sm btn-success save-node mt-2" type="button">
                <i class="fas fa-save"></i> Save
            </button>
        `;
    }

    updateContent(field, value) {
        if (!this.content) this.content = {};
        if (field === 'selected') {
            this.content.selected = value;
        } else if (field === 'currentPath') {
            this.content.currentPath = value;
        }
    }

    validate() {
        return Array.isArray(this.content.selected) && this.content.selected.length > 0;
    }

    toJSON() {
        return {
            ...super.toJSON(),
            content: {
                ...this.content,
                selected: this.content.selected,
                currentPath: this.content.currentPath
            }
        };
    }

    getNodeCardHtml(nodeIndex, context = {}) {
        if (!this.name) this.name = this.subtype || 'File';
        return super.getNodeCardHtml(nodeIndex, context);
    }
}

if (typeof window !== 'undefined') {
    window.FileDataNode = FileDataNode;
} 