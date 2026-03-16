// Shared chat components — canonical location: components/chat/
export { useToast, ToastStack } from '../../components/chat/ToastSystem'
export { ConvSidebar } from '../../components/chat/ConvSidebar'
export { WelcomeScreen } from '../../components/chat/WelcomeScreen'
export { UserMsg, AssistantMsg, CopyBtn, ThinkingIndicator } from '../../components/chat/MessageComponents'
export { Markdown } from '../../components/chat/Markdown'
export { StatsWidget } from '../../components/chat/StatsWidget'
export { SuggestedReplies } from '../../components/chat/SuggestedReplies'
export { ModelPicker } from '../../components/chat/ModelPicker'
export { ImagePreviews } from '../../components/chat/ImagePreviews'
export { ChatInputArea } from '../../components/chat/ChatInputArea'
export { FilePreviewCard, PastedContentCard, DragOverlay } from '../../components/chat/FilePreviewCards'
export { useChatFiles } from '../../components/chat/useChatFiles'
export { ShareModal } from '../../components/chat/ShareModal'

// Chat utilities (moved to lib/)
export {
  CHART_REVENUE, CHART_GRID, CHART_AXIS, CHART_CANCEL,
  CHART_BOOK, CHART_PALETTE, SUGGESTIONS,
  parseMessageDate, detectDataIntent, buildDataContext, getSuggestions,
} from '../../lib/chatUtils'
