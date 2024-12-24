# example requires websocket-client library:
# pip install websocket-client

import websocket
import ssl
import sys
import urllib.parse
import json
import threading
import time

# Enable debug output
websocket.enableTrace(True)

# Token and URL - match web client exactly
API_TOKEN = "5|vSI82152s7xen9bYBE2vqLciUhNJZo6OZPJIRVsO92ebbc8e"
url = f"wss://richbot9000.local:9501/app/{urllib.parse.quote(API_TOKEN)}"

# Global state
current_chat_id = None
is_running = True

def handle_message(ws, message):
    global current_chat_id
    try:
        data = json.loads(message)
        event = data.get('event')
        
        if event == 'connection_established':
            print(f"\nConnected with fd: {data.get('fd')}")
        
        elif event == 'chat_started':
            current_chat_id = data.get('chatId')
            print(f"\nChat started with ID: {current_chat_id}")
            print("Assistant info:", json.dumps(data.get('chatData', {}).get('assistant', {}), indent=2))
            
            # Send initial message
            time.sleep(1)  # Wait a bit before sending
            send_message(ws, "Hello! Can you help me with something?")
        
        elif event == 'message':
            print(f"\nReceived message: {data.get('message')}")
            if data.get('from') == 'assistant':
                # Wait a bit then send another message
                time.sleep(2)
                send_message(ws, "That's interesting! Tell me more.")
        
        elif event == 'state_update':
            clients = data.get('clients', {})
            chats = data.get('activeChats', {})
            print("\nState Update:")
            print(f"Connected clients: {len(clients)}")
            print(f"Active chats: {len(chats)}")
        
        else:
            print(f"\nUnhandled event: {event}")
            print(json.dumps(data, indent=2))
            
    except json.JSONDecodeError:
        print(f"Failed to parse message: {message}")
    except Exception as e:
        print(f"Error handling message: {e}")

def send_message(ws, text):
    if not current_chat_id:
        print("No active chat to send message to!")
        return
        
    message = {
        "event": "message",
        "chat_id": current_chat_id,
        "message": text
    }
    print(f"\nSending message: {text}")
    ws.send(json.dumps(message))

def input_loop(ws):
    global is_running
    print("\nEnter messages to send (or 'quit' to exit):")
    while is_running:
        try:
            message = input("> ")
            if message.lower() in ['quit', 'exit']:
                is_running = False
                break
            if message:
                send_message(ws, message)
        except EOFError:
            break
        except KeyboardInterrupt:
            break

# Create WebSocket with debug output
ws = websocket.WebSocket(sslopt={
    "cert_reqs": ssl.CERT_NONE,
    "check_hostname": False,
    "server_hostname": "richbot9000.local"
})

print(f"Connecting to {url}...")

try:
    ws.connect(url)
    print("Connected!")

    # Send start_chat event
    ws.send('{"event":"start_chat","target_type":"assistant","assistant_id":"test_assistant"}')
    print("Sent start_chat event")

    # Start input thread
    input_thread = threading.Thread(target=input_loop, args=(ws,))
    input_thread.daemon = True
    input_thread.start()

    # Main message loop
    while is_running:
        try:
            msg = ws.recv()
            handle_message(ws, msg)
        except websocket.WebSocketConnectionClosedException:
            print("Connection closed by server")
            break
        except Exception as e:
            print(f"Error receiving: {e}")
            print(f"Error type: {type(e).__name__}")
            break

except Exception as e:
    print(f"Connection failed: {e}")
    print(f"Error type: {type(e).__name__}")
    if hasattr(e, 'status_code'):
        print(f"Status code: {e.status_code}")
    sys.exit(1)

finally:
    is_running = False
    ws.close()
    print("\nConnection closed")
