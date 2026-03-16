import { useCallback, useEffect, useRef, useState } from 'react'
import { cn } from '../../lib/utils'

// ─────────────────────────────────────────────────────────────────────────────
// useTextStream — converted from TS, typewriter + fade modes
// ─────────────────────────────────────────────────────────────────────────────

export function useTextStream({
  textStream,
  speed = 20,
  mode = 'typewriter',
  onComplete,
  fadeDuration,
  segmentDelay,
  characterChunkSize,
  onError,
}) {
  const [displayedText, setDisplayedText] = useState('')
  const [isComplete,    setIsComplete]    = useState(false)
  const [segments,      setSegments]      = useState([])

  const speedRef             = useRef(speed)
  const modeRef              = useRef(mode)
  const currentIndexRef      = useRef(0)
  const animationRef         = useRef(null)
  const fadeDurationRef      = useRef(fadeDuration)
  const segmentDelayRef      = useRef(segmentDelay)
  const characterChunkSizeRef= useRef(characterChunkSize)
  const streamRef            = useRef(null)
  const completedRef         = useRef(false)
  const onCompleteRef        = useRef(onComplete)

  useEffect(() => {
    speedRef.current              = speed
    modeRef.current               = mode
    fadeDurationRef.current       = fadeDuration
    segmentDelayRef.current       = segmentDelay
    characterChunkSizeRef.current = characterChunkSize
  }, [speed, mode, fadeDuration, segmentDelay, characterChunkSize])

  useEffect(() => { onCompleteRef.current = onComplete }, [onComplete])

  const getChunkSize = useCallback(() => {
    if (typeof characterChunkSizeRef.current === 'number') return Math.max(1, characterChunkSizeRef.current)
    const s = Math.min(100, Math.max(1, speedRef.current))
    if (modeRef.current === 'typewriter') {
      if (s < 25) return 1
      return Math.max(1, Math.round((s - 25) / 10))
    }
    return 1
  }, [])

  const getProcessingDelay = useCallback(() => {
    if (typeof segmentDelayRef.current === 'number') return Math.max(0, segmentDelayRef.current)
    return Math.max(1, Math.round(100 / Math.sqrt(Math.min(100, Math.max(1, speedRef.current)))))
  }, [])

  const getFadeDuration = useCallback(() => {
    if (typeof fadeDurationRef.current === 'number') return Math.max(10, fadeDurationRef.current)
    return Math.round(1000 / Math.sqrt(Math.min(100, Math.max(1, speedRef.current))))
  }, [])

  const getSegmentDelay = useCallback(() => {
    if (typeof segmentDelayRef.current === 'number') return Math.max(0, segmentDelayRef.current)
    return Math.max(1, Math.round(100 / Math.sqrt(Math.min(100, Math.max(1, speedRef.current)))))
  }, [])

  const updateSegments = useCallback((text) => {
    if (modeRef.current !== 'fade') return
    try {
      const segmenter = new Intl.Segmenter(navigator.language, { granularity: 'word' })
      setSegments(Array.from(segmenter.segment(text)).map((s, i) => ({ text: s.segment, index: i })))
    } catch {
      setSegments(text.split(/(\s+)/).filter(Boolean).map((w, i) => ({ text: w, index: i })))
    }
  }, [])

  const markComplete = useCallback(() => {
    if (!completedRef.current) {
      completedRef.current = true
      setIsComplete(true)
      onCompleteRef.current?.()
    }
  }, [])

  const reset = useCallback(() => {
    currentIndexRef.current = 0
    setDisplayedText(''); setSegments([]); setIsComplete(false)
    completedRef.current = false
    if (animationRef.current) { cancelAnimationFrame(animationRef.current); animationRef.current = null }
  }, [])

  const processStringTypewriter = useCallback((text) => {
    let lastFrameTime = 0
    const tick = (timestamp) => {
      const delay = getProcessingDelay()
      if (delay > 0 && timestamp - lastFrameTime < delay) { animationRef.current = requestAnimationFrame(tick); return }
      lastFrameTime = timestamp
      if (currentIndexRef.current >= text.length) { markComplete(); return }
      const end = Math.min(currentIndexRef.current + getChunkSize(), text.length)
      const next = text.slice(0, end)
      setDisplayedText(next)
      if (modeRef.current === 'fade') updateSegments(next)
      currentIndexRef.current = end
      if (end < text.length) animationRef.current = requestAnimationFrame(tick)
      else markComplete()
    }
    animationRef.current = requestAnimationFrame(tick)
  }, [getProcessingDelay, getChunkSize, updateSegments, markComplete])

  const processAsyncIterable = useCallback(async (stream) => {
    const controller = new AbortController()
    streamRef.current = controller
    let displayed = ''
    try {
      for await (const chunk of stream) {
        if (controller.signal.aborted) return
        displayed += chunk
        setDisplayedText(displayed)
        updateSegments(displayed)
      }
      markComplete()
    } catch (err) {
      markComplete()
      onError?.(err)
    }
  }, [updateSegments, markComplete, onError])

  const startStreaming = useCallback(() => {
    reset()
    if (typeof textStream === 'string') processStringTypewriter(textStream)
    else if (textStream) processAsyncIterable(textStream)
  }, [textStream, reset, processStringTypewriter, processAsyncIterable])

  const pause  = useCallback(() => {
    if (animationRef.current) { cancelAnimationFrame(animationRef.current); animationRef.current = null }
  }, [])

  const resume = useCallback(() => {
    if (typeof textStream === 'string' && !isComplete) processStringTypewriter(textStream)
  }, [textStream, isComplete, processStringTypewriter])

  useEffect(() => {
    startStreaming()
    return () => {
      if (animationRef.current) cancelAnimationFrame(animationRef.current)
      if (streamRef.current) streamRef.current.abort()
    }
  }, [textStream]) // eslint-disable-line react-hooks/exhaustive-deps

  return { displayedText, isComplete, segments, getFadeDuration, getSegmentDelay, reset, startStreaming, pause, resume }
}

// ─────────────────────────────────────────────────────────────────────────────
// ResponseStream component
// ─────────────────────────────────────────────────────────────────────────────

const fadeStyle = `
  @keyframes rsiFadeIn { from { opacity: 0 } to { opacity: 1 } }
  .rsi-fade-segment { display:inline-block; opacity:0; animation: rsiFadeIn var(--rsi-dur,300ms) ease-out forwards }
  .rsi-fade-space   { white-space: pre }
`

export function ResponseStream({
  textStream,
  mode = 'typewriter',
  speed = 20,
  className = '',
  onComplete,
  as: Tag = 'div',
  fadeDuration,
  segmentDelay,
  characterChunkSize,
}) {
  const animationEndRef = useRef(null)
  const { displayedText, isComplete, segments, getFadeDuration, getSegmentDelay } = useTextStream({
    textStream, speed, mode, onComplete, fadeDuration, segmentDelay, characterChunkSize,
  })
  useEffect(() => { animationEndRef.current = onComplete ?? null }, [onComplete])

  const handleLastEnd = useCallback(() => {
    if (animationEndRef.current && isComplete) animationEndRef.current()
  }, [isComplete])

  if (mode === 'fade') {
    const dur = getFadeDuration()
    const del = getSegmentDelay()
    return (
      <Tag className={className}>
        <style>{fadeStyle}</style>
        <div className="relative">
          {segments.map((seg, idx) => (
            <span
              key={seg.index}
              className={cn('rsi-fade-segment', /^\s+$/.test(seg.text) && 'rsi-fade-space')}
              style={{ '--rsi-dur': `${dur}ms`, animationDelay: `${idx * del}ms` }}
              onAnimationEnd={idx === segments.length - 1 ? handleLastEnd : undefined}
            >
              {seg.text}
            </span>
          ))}
        </div>
      </Tag>
    )
  }

  return <Tag className={className}>{displayedText}</Tag>
}
