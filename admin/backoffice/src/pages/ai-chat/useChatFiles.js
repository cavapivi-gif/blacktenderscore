import { useCallback, useState } from 'react'

// ─────────────────────────────────────────────────────────────────────────────
// useChatFiles — file attachment + pasted-content state management
// ─────────────────────────────────────────────────────────────────────────────

const MAX_FILES      = 10
const MAX_FILE_SIZE  = 50 * 1024 * 1024 // 50 MB
const PASTE_THRESHOLD = 200

const TEXTUAL_EXTS = new Set([
  'txt','md','py','js','ts','jsx','tsx','html','htm','css','scss','sass',
  'json','xml','yaml','yml','csv','sql','sh','bash','php','rb','go','java',
  'c','cpp','h','hpp','cs','rs','swift','kt','scala','r','vue','svelte',
  'astro','config','conf','ini','toml','log',
])

export function isTextualFile(file) {
  const ext = file.name.split('.').pop()?.toLowerCase() ?? ''
  if (TEXTUAL_EXTS.has(ext)) return true
  const lower = file.name.toLowerCase()
  if (lower.includes('readme') || lower.includes('dockerfile') || lower.includes('makefile')) return true
  return file.type.startsWith('text/') || ['application/json','application/xml','application/javascript'].includes(file.type)
}

function readAsText(file) {
  return new Promise((resolve, reject) => {
    const r = new FileReader()
    r.onload  = e => resolve(e.target?.result ?? '')
    r.onerror = reject
    r.readAsText(file)
  })
}

export function useChatFiles() {
  const [files,         setFiles]         = useState([])
  const [pastedContent, setPastedContent] = useState([])
  const [isDragging,    setIsDragging]    = useState(false)

  const addFiles = useCallback((fileList) => {
    if (!fileList) return
    const available = MAX_FILES - files.length
    if (available <= 0) return
    const toAdd = Array.from(fileList).slice(0, available).filter(f => f.size <= MAX_FILE_SIZE)

    const newEntries = toAdd.map(file => ({
      id:             `f_${Date.now()}_${Math.random()}`,
      file,
      preview:        file.type.startsWith('image/') ? URL.createObjectURL(file) : undefined,
      type:           file.type || 'application/octet-stream',
      uploadStatus:   'pending',
      uploadProgress: 0,
    }))

    setFiles(prev => [...prev, ...newEntries])

    newEntries.forEach(entry => {
      if (isTextualFile(entry.file)) {
        readAsText(entry.file)
          .then(text  => setFiles(p => p.map(f => f.id === entry.id ? { ...f, textContent: text } : f)))
          .catch(()   => setFiles(p => p.map(f => f.id === entry.id ? { ...f, textContent: '' } : f)))
      }
      // Simulate upload progress
      setFiles(p => p.map(f => f.id === entry.id ? { ...f, uploadStatus: 'uploading' } : f))
      let progress = 0
      const iv = setInterval(() => {
        progress += Math.random() * 25 + 5
        if (progress >= 100) {
          clearInterval(iv)
          setFiles(p => p.map(f => f.id === entry.id ? { ...f, uploadStatus: 'complete', uploadProgress: 100 } : f))
        } else {
          setFiles(p => p.map(f => f.id === entry.id ? { ...f, uploadProgress: progress } : f))
        }
      }, 120)
    })
  }, [files.length])

  const removeFile = useCallback((id) => {
    setFiles(prev => {
      const f = prev.find(x => x.id === id)
      if (f?.preview) URL.revokeObjectURL(f.preview)
      return prev.filter(x => x.id !== id)
    })
  }, [])

  const addPastedContent = useCallback((text) => {
    if (text.length <= PASTE_THRESHOLD || pastedContent.length >= 5) return false
    setPastedContent(prev => [...prev, {
      id:        `p_${Date.now()}`,
      content:   text,
      timestamp: new Date(),
      wordCount: text.split(/\s+/).filter(Boolean).length,
    }])
    return true
  }, [pastedContent.length])

  const removePasted = useCallback((id) => {
    setPastedContent(prev => prev.filter(c => c.id !== id))
  }, [])

  const clearAll = useCallback(() => {
    setFiles(prev => { prev.forEach(f => f.preview && URL.revokeObjectURL(f.preview)); return [] })
    setPastedContent([])
  }, [])

  const onDragOver  = useCallback(e => { e.preventDefault(); setIsDragging(true)  }, [])
  const onDragLeave = useCallback(e => { e.preventDefault(); setIsDragging(false) }, [])
  const onDrop      = useCallback(e => { e.preventDefault(); setIsDragging(false); addFiles(e.dataTransfer.files) }, [addFiles])

  return {
    files, pastedContent, isDragging,
    addFiles, removeFile, addPastedContent, removePasted, clearAll,
    onDragOver, onDragLeave, onDrop,
    hasUploading: files.some(f => f.uploadStatus === 'uploading'),
  }
}
