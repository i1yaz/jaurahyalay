import { sortTablePositions, addSerialNumber, checkRecentUpdates } from "./common"

const STORAGE_KEY = "visitor_id"
const WS_PATH = "/ws/visitors"
let websocket
let heartbeatTimeout
let reconnectTimeout
let reconnectAttempts = 0

function safeStorageGet(key) {
  try {
    return localStorage.getItem(key)
  } catch {
    return null
  }
}

function safeStorageSet(key, value) {
  try {
    localStorage.setItem(key, value)
  } catch {
    // ignore
  }
}

function getVisitorId() {
  return safeStorageGet(STORAGE_KEY)
}

function setVisitorId(value) {
  if (!value) return
  safeStorageSet(STORAGE_KEY, value)
}

function updateOnlineUsers(liveActive, rollingActive) {
  const element = document.getElementById("online-users")
  if (!element) return
  // element.innerText = `Live (${liveActive}) Active (${rollingActive})`
  element.innerText = `Online Users ${rollingActive}`
}

function getWebSocketUrl() {
  const domain = window.location.hostname
  const configuredSocketServer = import.meta.env.VITE_SOCKET_SERVER || "wss://live.pigeonclub.top"
  const base = configuredSocketServer

  const params = new URLSearchParams({ domain })
  const visitorId = getVisitorId()
  if (visitorId) {
    params.set("visitor_id", visitorId)
  }

  return `${base}${WS_PATH}?${params.toString()}`
}

function clearHeartbeat() {
  if (heartbeatTimeout) {
    clearTimeout(heartbeatTimeout)
    heartbeatTimeout = null
  }
}

function scheduleHeartbeat() {
  clearHeartbeat()
  heartbeatTimeout = setTimeout(() => {
    if (websocket && websocket.readyState === WebSocket.OPEN) {
      websocket.send(JSON.stringify({ type: "heartbeat" }))
    }
    scheduleHeartbeat()
  }, 20000)
}

function cleanupReconnect() {
  if (reconnectTimeout) {
    clearTimeout(reconnectTimeout)
    reconnectTimeout = null
  }
}

function connectWebSocket() {
  const wsUrl = getWebSocketUrl()
  websocket = new WebSocket(wsUrl)

  websocket.onopen = () => {
    reconnectAttempts = 0
    cleanupReconnect()
    scheduleHeartbeat()
    console.debug("WebSocket connected", wsUrl)
  }

  websocket.onmessage = (event) => {
    try {
      const message = JSON.parse(event.data)
      if (message.visitor_id) {
        setVisitorId(message.visitor_id)
      }
      if (typeof message.live_active === "number" && typeof message.rolling_active === "number") {
        updateOnlineUsers(message.live_active, message.rolling_active)
      }
      // keep old behavior if needed
      // if (message.type === "score-update") { ... }
    } catch (error) {
      console.error("Failed to parse WS message", error, event.data)
    }
  }

  websocket.onerror = (event) => {
    console.error("WebSocket error", event)
  }

  websocket.onclose = (event) => {
    console.warn("WebSocket closed", event.code, event.reason)
    clearHeartbeat()

    const delay = Math.min(30000, 1000 * 2 ** reconnectAttempts)
    reconnectAttempts += 1
    cleanupReconnect()
    reconnectTimeout = setTimeout(connectWebSocket, delay)
  }
}

function initWebSocket() {
  if (!window.WebSocket) {
    console.warn("WebSocket not supported")
    return
  }
  connectWebSocket()
}

// start automatically when module loads
initWebSocket()

export { initWebSocket, updateOnlineUsers }

