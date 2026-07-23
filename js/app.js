// js/app.js
document.addEventListener('DOMContentLoaded', () => {
    // Initialize settings
    loadSettings();

    // Route dashboard vs reader
    if (document.getElementById('uploadZone')) {
        initDashboard();
    }
    
    if (document.getElementById('readerWorkspace')) {
        initReader();
    }
});

// --- SETTINGS MANAGEMENT ---
let settings = {
    engine: 'google',
    geminiKey: ''
};

function loadSettings() {
    const savedEngine = localStorage.getItem('translation_engine');
    const savedKey = localStorage.getItem('gemini_key');
    
    if (savedEngine) {
        settings.engine = savedEngine;
    }
    if (savedKey) {
        settings.geminiKey = savedKey;
    }

    // Update settings DOM elements if they exist
    const engineGoogle = document.getElementById('engineGoogle');
    const engineGemini = document.getElementById('engineGemini');
    const geminiApiKey = document.getElementById('geminiApiKey');
    
    if (engineGoogle && engineGemini) {
        if (settings.engine === 'gemini') {
            engineGemini.checked = true;
            document.getElementById('engineGoogleCard').classList.remove('active');
            document.getElementById('engineGeminiCard').classList.add('active');
            if (document.getElementById('geminiKeyGroup')) {
                document.getElementById('geminiKeyGroup').style.display = 'block';
            }
        } else {
            engineGoogle.checked = true;
            document.getElementById('engineGeminiCard').classList.remove('active');
            document.getElementById('engineGoogleCard').classList.add('active');
            if (document.getElementById('geminiKeyGroup')) {
                document.getElementById('geminiKeyGroup').style.display = 'none';
            }
        }
    }
    
    if (geminiApiKey) {
        geminiApiKey.value = settings.geminiKey;
    }

    updateHeaderBadges();
}

function updateHeaderBadges() {
    const badge = document.getElementById('apiKeyStatus') || document.getElementById('readerApiKeyStatus');
    if (!badge) return;

    if (settings.engine === 'gemini') {
        if (settings.geminiKey) {
            badge.className = 'api-badge api-badge-active';
            badge.innerHTML = '<i class="fa-solid fa-brain"></i> Gemini AI Active';
        } else {
            badge.className = 'api-badge api-badge-inactive';
            badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Key Missing';
        }
    } else {
        badge.className = 'api-badge api-badge-active';
        badge.style.background = 'rgba(99, 102, 241, 0.15)';
        badge.style.color = '#c7d2fe';
        badge.style.border = '1px solid rgba(99, 102, 241, 0.3)';
        badge.innerHTML = '<i class="fa-solid fa-language"></i> Google Translate';
    }
}

// --- DASHBOARD LOGIC ---
function initDashboard() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const statusMessage = document.querySelector('#statusMessage span');
    
    // Toggle engine settings UI
    const googleCard = document.getElementById('engineGoogleCard');
    const geminiCard = document.getElementById('engineGeminiCard');
    const keyGroup = document.getElementById('geminiKeyGroup');
    const geminiRadio = document.getElementById('engineGemini');
    const googleRadio = document.getElementById('engineGoogle');
    
    googleCard.addEventListener('click', () => {
        googleRadio.checked = true;
        googleCard.classList.add('active');
        geminiCard.classList.remove('active');
        keyGroup.style.display = 'none';
    });
    
    geminiCard.addEventListener('click', () => {
        geminiRadio.checked = true;
        geminiCard.classList.add('active');
        googleCard.classList.remove('active');
        keyGroup.style.display = 'block';
    });

    // Toggle API Key visibility
    const toggleEye = document.getElementById('togglePasswordVisibility');
    const geminiApiKey = document.getElementById('geminiApiKey');
    if (toggleEye && geminiApiKey) {
        toggleEye.addEventListener('click', () => {
            if (geminiApiKey.type === 'password') {
                geminiApiKey.type = 'text';
                toggleEye.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            } else {
                geminiApiKey.type = 'password';
                toggleEye.innerHTML = '<i class="fa-solid fa-eye"></i>';
            }
        });
    }

    // Save Settings button
    document.getElementById('saveSettingsBtn').addEventListener('click', () => {
        const selectedEngine = document.querySelector('input[name="translation_engine"]:checked').value;
        const key = geminiApiKey.value.trim();
        
        if (selectedEngine === 'gemini' && !key) {
            alert('Please enter your Gemini API Key to use Ultra AI Translation.');
            return;
        }
        
        localStorage.setItem('translation_engine', selectedEngine);
        localStorage.setItem('gemini_key', key);
        
        settings.engine = selectedEngine;
        settings.geminiKey = key;
        
        updateHeaderBadges();
        
        // Dynamic save feedback
        const btn = document.getElementById('saveSettingsBtn');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Settings Saved!';
        btn.style.background = 'var(--secondary)';
        setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.style.background = '';
        }, 2000);
    });

    // Test API Key button logic
    const testApiBtn = document.getElementById('testApiBtn');
    if (testApiBtn) {
        testApiBtn.addEventListener('click', () => {
            const key = geminiApiKey.value.trim();
            const text = document.getElementById('testParagraph').value.trim();
            const resultDiv = document.getElementById('testResult');
            
            if (!key) {
                alert('Please enter a Gemini API Key to test.');
                return;
            }
            if (!text) {
                alert('Please enter some text to translate.');
                return;
            }
            
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Testing translation...';
            resultDiv.style.color = 'var(--text-secondary)';
            
            const params = new URLSearchParams();
            params.append('q', text);
            params.append('engine', 'gemini');
            params.append('gemini_key', key);
            
            fetch(`translate_api.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div style="color: #34d399; margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-check"></i> Success! Translation:</div>
                    <div style="padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 0.25rem; font-family: 'Amiri', serif; font-size: 1.1rem; direction: rtl; color: var(--text-primary); line-height: 1.6;">${data.text_ar}</div>`;
                } else {
                    resultDiv.innerHTML = `<div style="color: #f87171;"><i class="fa-solid fa-circle-xmark"></i> Failed: ${data.error}</div>`;
                }
            })
            .catch(err => {
                resultDiv.innerHTML = `<div style="color: #f87171;"><i class="fa-solid fa-circle-xmark"></i> Network Error: ${err.message}</div>`;
            });
        });
    }

    // Drag and Drop Upload Zone triggers
    uploadZone.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            uploadFile(fileInput.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        }, false);
    });

    uploadZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            uploadFile(files[0]);
        }
    });

    // Upload function
    function uploadFile(file) {
        uploadStatus.style.display = 'block';
        statusMessage.textContent = `Uploading and processing "${file.name}"...`;
        
        // Reset banner styling in case of previous failure
        const banner = document.getElementById('statusMessage');
        banner.className = 'alert-banner alert-info';
        banner.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Uploading and parsing document...</span>';
        const msgText = banner.querySelector('span');
        msgText.textContent = `Uploading and parsing "${file.name}"...`;

        const formData = new FormData();
        formData.append('document', file);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                msgText.textContent = 'Document parsed successfully! Opening Reader...';
                banner.className = 'alert-banner';
                banner.style.background = 'rgba(16, 185, 129, 0.15)';
                banner.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                banner.style.color = '#34d399';
                banner.querySelector('i').className = 'fa-solid fa-circle-check';
                setTimeout(() => {
                    window.location.href = `reader.php?id=${data.document_id}`;
                }, 1000);
            } else {
                throw new Error(data.error || 'Unknown parsing error occurred.');
            }
        })
        .catch(err => {
            banner.className = 'alert-banner btn-danger';
            banner.style.background = 'rgba(239, 68, 68, 0.15)';
            banner.style.borderColor = 'rgba(239, 68, 68, 0.3)';
            banner.style.color = '#f87171';
            banner.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> <span>Error: ${err.message}</span>`;
        });
    }

    // Document Deletion click handlers
    document.querySelectorAll('.delete-doc-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const row = btn.closest('.history-item');
            const title = row.querySelector('.doc-title').textContent.trim();
            
            if (confirm(`Are you sure you want to delete "${title}"? This will delete all translations too.`)) {
                const formData = new FormData();
                formData.append('document_id', id);
                
                fetch('delete_document.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            row.remove();
                            // Check if empty
                            const list = document.getElementById('documentLibraryList');
                            if (list.children.length === 0) {
                                document.getElementById('emptyState').style.display = 'flex';
                            }
                        }, 300);
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(() => alert('Network error occurred while deleting document.'));
            }
        });
    });
}

// --- READER LOGIC ---
let translationQueue = [];
let queueIsRunning = false;
let synthesizedVoice = null;

function initReader() {
    const workspace = document.getElementById('readerWorkspace');
    
    // Layout switcher configuration
    const btnSide = document.getElementById('btnLayoutSide');
    const btnStacked = document.getElementById('btnLayoutStacked');
    
    btnSide.addEventListener('click', () => {
        workspace.classList.remove('layout-stacked');
        btnSide.classList.add('active');
        btnStacked.classList.remove('active');
    });

    btnStacked.addEventListener('click', () => {
        workspace.classList.add('layout-stacked');
        btnStacked.classList.add('active');
        btnSide.classList.remove('active');
    });

    // Wrap words in each English paragraph block using chunking to prevent freezing
    const panesEn = Array.from(document.querySelectorAll('.pane-en'));
    let paneIndex = 0;
    
    function processPanesChunk() {
        const chunkEnd = Math.min(paneIndex + 20, panesEn.length);
        for (; paneIndex < chunkEnd; paneIndex++) {
            const pane = panesEn[paneIndex];
            const text = pane.textContent.trim();
            if (text && !pane.hasAttribute('data-wrapped')) {
                // Match any English word, numbers, apostrophes and hyphens
                // Wrap in span with clickable word class
                const html = text.replace(/([a-zA-Z0-9'-]+)/g, '<span class="word-clickable">$1</span>');
                pane.innerHTML = html;
                pane.setAttribute('data-wrapped', 'true');
            }
        }
        if (paneIndex < panesEn.length) {
            requestAnimationFrame(processPanesChunk); // Yield to browser to keep UI responsive
        }
    }
    if (panesEn.length > 0) {
        requestAnimationFrame(processPanesChunk);
    }

    // Event delegation for word click lookup
    workspace.addEventListener('click', (e) => {
        if (e.target.classList.contains('word-clickable')) {
            const word = e.target.textContent;
            lookupWord(word, e.target);
        }
    });

    // Dismiss dictionary popup when clicking elsewhere
    document.addEventListener('click', (e) => {
        const popup = document.getElementById('dictPopup');
        if (popup && popup.style.display === 'block') {
            if (!popup.contains(e.target) && !e.target.classList.contains('word-clickable')) {
                closeDictPopup();
            }
        }
    });

    // Setup speech synthesis pronunciation on click of the popup speaker
    document.getElementById('dictPronounceBtn').addEventListener('click', () => {
        const word = window.currentLookupWord;
        if (word && ('speechSynthesis' in window)) {
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(word);
            utterance.lang = 'en-US';
            if (synthesizedVoice) utterance.voice = synthesizedVoice;
            window.speechSynthesis.speak(utterance);
        }
    });

    // Close dict on ESC key press
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDictPopup();
    });

    // Play TTS speech synthesis for whole paragraph blocks
    document.querySelectorAll('.btn-tts-play').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = btn.getAttribute('data-index');
            const block = document.getElementById(`block-${idx}`);
            const paneEn = document.getElementById(`en-${idx}`);
            
            if ('speechSynthesis' in window) {
                // If already speaking this block, stop
                if (block.classList.contains('speaking-active')) {
                    window.speechSynthesis.cancel();
                    block.classList.remove('speaking-active');
                    btn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
                    return;
                }
                
                // Clear any other speaking blocks
                document.querySelectorAll('.paragraph-block.speaking-active').forEach(b => {
                    b.classList.remove('speaking-active');
                    const otherIdx = b.getAttribute('data-index');
                    const otherBtn = document.querySelector(`.btn-tts-play[data-index="${otherIdx}"]`);
                    if (otherBtn) otherBtn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
                });
                
                window.speechSynthesis.cancel();
                
                // Set text (exclude clickable span html, just read raw text)
                const utterance = new SpeechSynthesisUtterance(paneEn.textContent);
                utterance.lang = 'en-US';
                
                if (synthesizedVoice) utterance.voice = synthesizedVoice;
                
                block.classList.add('speaking-active');
                btn.innerHTML = '<i class="fa-solid fa-circle-stop" style="color: var(--secondary);"></i>';
                
                utterance.onend = () => {
                    block.classList.remove('speaking-active');
                    btn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
                };
                
                utterance.onerror = () => {
                    block.classList.remove('speaking-active');
                    btn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
                };
                
                window.speechSynthesis.speak(utterance);
            } else {
                alert('Text-to-speech is not supported in your browser.');
            }
        });
    });

    // Re-translate paragraph click listener
    document.querySelectorAll('.btn-retranslate').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = parseInt(btn.getAttribute('data-index'));
            triggerSingleTranslate(idx);
        });
    });

    // Populate voice synthesiser
    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = () => {
            const voices = window.speechSynthesis.getVoices();
            // Look for Google US English or standard English voice
            synthesizedVoice = voices.find(v => v.lang === 'en-US' && v.name.includes('Google')) ||
                                voices.find(v => v.lang.startsWith('en-US')) ||
                                voices.find(v => v.lang.startsWith('en')) ||
                                voices[0];
        };
    }

    // Auto queue translation of pending items on startup
    // enqueuePendingTranslations(); // Disabled to allow manual clicking per paragraph

    // Trigger translate remaining items click listener
    document.getElementById('btnTranslateAll').addEventListener('click', () => {
        enqueuePendingTranslations(true); // force translating everything including failed ones
    });
}

function closeDictPopup() {
    const popup = document.getElementById('dictPopup');
    if (popup) popup.style.display = 'none';
}

// --- WORD LOOKUP SYSTEM ---
async function lookupWord(word, targetSpan) {
    // Strip punctuation from word for lookup
    const cleanWord = word.replace(/[^a-zA-Z0-9'-]/g, '').trim();
    if (!cleanWord) return;

    const popup = document.getElementById('dictPopup');
    popup.style.display = 'block';
    
    // Position lookup modal
    const rect = targetSpan.getBoundingClientRect();
    const scrollY = window.scrollY || window.pageYOffset;
    const scrollX = window.scrollX || window.pageXOffset;
    
    // Render initially offscreen to get dimensions
    popup.style.top = '-1000px';
    popup.style.left = '-1000px';
    
    setTimeout(() => {
        let top = rect.top + scrollY - popup.offsetHeight - 12;
        // Position below if there is no space above
        if (rect.top - popup.offsetHeight - 12 < 0) {
            top = rect.bottom + scrollY + 12;
        }
        
        let left = rect.left + scrollX + (rect.width / 2) - (popup.offsetWidth / 2);
        // Bind boundary checks
        left = Math.max(10, Math.min(left, window.innerWidth - popup.offsetWidth - 10));
        
        popup.style.top = top + 'px';
        popup.style.left = left + 'px';
    }, 10);

    document.getElementById('dictWord').textContent = cleanWord;
    document.getElementById('dictPhonetic').textContent = 'Fetching pronunciation...';
    document.getElementById('dictMeanings').innerHTML = '<div style="padding: 0.5rem 0;"><i class="fa-solid fa-circle-notch fa-spin"></i> Searching dictionary...</div>';
    document.getElementById('dictTranslation').innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Translating word...';

    window.currentLookupWord = cleanWord;

    // Fetch Dictionary definition from API
    const dictPromise = fetch(`https://api.dictionaryapi.dev/api/v2/entries/en/${encodeURIComponent(cleanWord.toLowerCase())}`)
        .then(res => res.ok ? res.json() : null)
        .catch(() => null);

    // Fetch translation using our backend API to proxy Google Translate and avoid CORS limits
    const translateUrl = `translate_api.php?q=${encodeURIComponent(cleanWord)}&engine=google`;
    const transPromise = fetch(translateUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.text_ar) {
                return data.text_ar;
            }
            return 'No translation found';
        })
        .catch(() => 'Translation error');

    try {
        const [dictData, arabicWord] = await Promise.all([dictPromise, transPromise]);
        
        document.getElementById('dictTranslation').textContent = arabicWord;
        
        if (dictData && dictData[0]) {
            const entry = dictData[0];
            document.getElementById('dictPhonetic').textContent = entry.phonetic || 
                (entry.phonetics && entry.phonetics.find(p => p.text)?.text) || '';
            
            let html = '';
            if (entry.meanings && entry.meanings.length > 0) {
                entry.meanings.slice(0, 2).forEach(meaning => {
                    html += `<div class="dict-part">${meaning.partOfSpeech}</div>`;
                    meaning.definitions.slice(0, 2).forEach(def => {
                        html += `<div class="dict-def">${def.definition}</div>`;
                    });
                });
            } else {
                html = '<div style="padding: 0.5rem 0;">No definition entries.</div>';
            }
            document.getElementById('dictMeanings').innerHTML = html;
        } else {
            document.getElementById('dictPhonetic').textContent = 'No phonetic info';
            document.getElementById('dictMeanings').innerHTML = '<div style="padding: 0.5rem 0; color: var(--text-muted);">Word definition not found in public dictionary.</div>';
        }
    } catch(err) {
        document.getElementById('dictTranslation').textContent = 'Lookup failed';
        document.getElementById('dictMeanings').innerHTML = '<div style="padding: 0.5rem 0; color: var(--text-muted);">Error fetching dictionary entries.</div>';
    }
}

// --- TRANSLATION RUNNER SYSTEM ---
function enqueuePendingTranslations(force = false) {
    const pendingPanes = [];
    
    document.querySelectorAll('.pane-ar').forEach(pane => {
        const status = pane.getAttribute('data-status');
        const idx = parseInt(pane.closest('.paragraph-block').getAttribute('data-index'));
        
        if (status === 'pending' || (force && status === 'failed')) {
            if (!translationQueue.includes(idx)) {
                translationQueue.push(idx);
                pendingPanes.push(pane);
            }
        }
    });

    // Chunk the DOM updates so the browser doesn't freeze
    let updateIndex = 0;
    function updatePanesChunk() {
        const chunkEnd = Math.min(updateIndex + 25, pendingPanes.length);
        for (; updateIndex < chunkEnd; updateIndex++) {
            const pane = pendingPanes[updateIndex];
            pane.setAttribute('data-status', 'pending');
            pane.innerHTML = `
                <div class="translation-loading">
                    <span class="spinner"></span>
                    <span>Translating...</span>
                </div>`;
        }
        if (updateIndex < pendingPanes.length) {
            requestAnimationFrame(updatePanesChunk);
        } else if (translationQueue.length > 0 && !queueIsRunning) {
            processTranslationQueue();
        }
    }
    
    if (pendingPanes.length > 0) {
        requestAnimationFrame(updatePanesChunk);
    }
}

function triggerSingleTranslate(idx) {
    const pane = document.getElementById(`ar-${idx}`);
    pane.setAttribute('data-status', 'pending');
    pane.innerHTML = `
        <div class="translation-loading">
            <span class="spinner"></span>
            <span>Translating...</span>
        </div>`;
        
    if (!translationQueue.includes(idx)) {
        // Put in front of queue
        translationQueue.unshift(idx);
    }
    
    if (!queueIsRunning) {
        processTranslationQueue();
    }
}

function translateParagraph(docId, idx) {
    triggerSingleTranslate(idx);
}

function processTranslationQueue() {
    if (translationQueue.length === 0) {
        queueIsRunning = false;
        return;
    }

    queueIsRunning = true;
    const currentIdx = translationQueue[0];
    const paneAr = document.getElementById(`ar-${currentIdx}`);
    
    paneAr.setAttribute('data-status', 'translating');

    // Build API query parameters
    const params = new URLSearchParams();
    params.append('document_id', CURRENT_DOCUMENT_ID);
    params.append('paragraph_index', currentIdx);
    params.append('engine', settings.engine);
    params.append('gemini_key', settings.geminiKey);

    fetch(`translate_api.php?${params.toString()}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            paneAr.setAttribute('data-status', 'translated');
            paneAr.textContent = data.text_ar;
            
            // Remove from queue
            translationQueue.shift();
            
            // Recalculate stats and progress
            updateReaderStats();
        } else {
            throw new Error(data.error || 'Empty translation payload returned.');
        }
    })
    .catch(err => {
        console.error('Translation error:', err);
        paneAr.setAttribute('data-status', 'failed');
        paneAr.innerHTML = `
            <div class="translation-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Translation failed: ${err.message}. Click to retry.</span>
            </div>`;
            
        // Still shift to prevent getting stuck
        translationQueue.shift();
    })
    .finally(() => {
        // Yield to browser thread slightly, then continue
        setTimeout(processTranslationQueue, 400);
    });
}

function updateReaderStats() {
    const translatedCount = document.querySelectorAll('.pane-ar[data-status="translated"]').length;

    // Update numbers
    const span = document.getElementById('translatedCountSpan');
    if (span) span.textContent = translatedCount;
    
    // Update progress bar
    const bar = document.getElementById('readerProgressBar');
    if (bar && TOTAL_PARAGRAPHS > 0) {
        const percent = Math.round((translatedCount / TOTAL_PARAGRAPHS) * 100);
        bar.style.width = `${percent}%`;
    }
}
