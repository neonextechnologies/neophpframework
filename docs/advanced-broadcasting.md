# Broadcasting

Real-time event broadcasting with WebSockets and Pusher.

## Configuration

### Setup Broadcasting

```php
// config/broadcasting.php
return [
    'default' => env('BROADCAST_DRIVER', 'pusher'),
    
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'encrypted' => true,
            ],
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
        
        'log' => [
            'driver' => 'log',
        ],
    ],
];
```

## Broadcasting Events

### Broadcast Event

```php
namespace App\Events;

use NeoPhp\Broadcasting\ShouldBroadcast;

class OrderShipped implements ShouldBroadcast
{
    public function __construct(
        public Order $order
    ) {}
    
    public function broadcastOn(): array
    {
        return ['orders.' . $this->order->id];
    }
    
    public function broadcastAs(): string
    {
        return 'order.shipped';
    }
    
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
        ];
    }
}
```

### Dispatch Broadcast

```php
use App\Events\OrderShipped;

// Broadcast event
event(new OrderShipped($order));

// Broadcast to specific users
broadcast(new OrderShipped($order))->toOthers();
```

## Presence Channels

### Private Channels

```php
class MessageSent implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return ['private-chat.' . $this->chat->id];
    }
}
```

### Presence Channels

```php
class UserJoined implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return ['presence-room.' . $this->room->id];
    }
}
```

## Channel Authorization

### Define Authorization

```php
// routes/channels.php

// Private channel
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return $user->chats->contains('id', $chatId);
});

// Presence channel
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    if ($user->canJoinRoom($roomId)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }
});
```

## Client-Side Integration

### Pusher JavaScript

```javascript
import Pusher from 'pusher-js';

// Initialize Pusher
const pusher = new Pusher('YOUR_APP_KEY', {
    cluster: 'mt1',
    encrypted: true,
    authEndpoint: '/broadcasting/auth',
});

// Subscribe to channel
const channel = pusher.subscribe('orders.1');

// Listen to events
channel.bind('order.shipped', function(data) {
    console.log('Order shipped:', data);
    showNotification('Your order has shipped!');
});

// Private channel
const privateChannel = pusher.subscribe('private-chat.1');

privateChannel.bind('message.sent', function(data) {
    appendMessage(data.message);
});

// Presence channel
const presenceChannel = pusher.subscribe('presence-room.1');

presenceChannel.bind('pusher:subscription_succeeded', function(members) {
    console.log('Users online:', members.count);
    members.each(function(member) {
        addUser(member.info);
    });
});

presenceChannel.bind('pusher:member_added', function(member) {
    addUser(member.info);
});

presenceChannel.bind('pusher:member_removed', function(member) {
    removeUser(member.info);
});
```

### Laravel Echo

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'YOUR_APP_KEY',
    cluster: 'mt1',
    encrypted: true,
});

// Listen to channel
Echo.channel('orders.1')
    .listen('OrderShipped', (e) => {
        console.log(e.order);
    });

// Private channel
Echo.private('chat.1')
    .listen('MessageSent', (e) => {
        console.log(e.message);
    });

// Presence channel
Echo.join('room.1')
    .here((users) => {
        console.log('Currently in room:', users);
    })
    .joining((user) => {
        console.log('User joined:', user.name);
    })
    .leaving((user) => {
        console.log('User left:', user.name);
    })
    .listen('MessageSent', (e) => {
        console.log('Message:', e.message);
    });
```

## Broadcasting Notifications

### Broadcast Notification

```php
namespace App\Notifications;

use NeoPhp\Notifications\Notification;
use NeoPhp\Broadcasting\BroadcastChannel;

class InvoicePaid extends Notification
{
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }
    
    public function toBroadcast($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->amount,
        ];
    }
    
    public function broadcastType(): string
    {
        return 'invoice.paid';
    }
}
```

### Listen to Notifications

```javascript
Echo.private(`user.${userId}`)
    .notification((notification) => {
        console.log('Notification:', notification);
        showNotification(notification.message);
    });
```

## Redis Broadcasting

### Setup Redis

```bash
# Install Redis
composer require predis/predis

# Start Redis server
redis-server

# Start queue worker
php artisan queue:work redis
```

### Socket.io Server

```javascript
// server.js
const app = require('express')();
const server = require('http').Server(app);
const io = require('socket.io')(server);
const Redis = require('ioredis');

const redis = new Redis();

server.listen(6001);

redis.psubscribe('*', function(err, count) {});

redis.on('pmessage', function(pattern, channel, message) {
    message = JSON.parse(message);
    io.emit(channel + ':' + message.event, message.data);
});
```

### Client Connection

```javascript
import io from 'socket.io-client';

const socket = io('http://localhost:6001');

socket.on('orders.1:order.shipped', (data) => {
    console.log('Order shipped:', data);
});
```

## Testing Broadcasting

### Assert Broadcasts

```php
use NeoPhp\Testing\Facades\Event;

public function test_event_is_broadcasted()
{
    Event::fake([OrderShipped::class]);
    
    // Perform action
    $this->post('/orders/1/ship');
    
    // Assert event was broadcasted
    Event::assertDispatched(OrderShipped::class, function ($event) {
        return $event->order->id === 1;
    });
}
```

## Best Practices

1. **Authorize Channels** - Always authorize private channels
2. **Minimal Data** - Only broadcast necessary data
3. **Queue Events** - Queue broadcast events for performance
4. **Error Handling** - Handle connection errors gracefully
5. **Reconnection** - Implement automatic reconnection
6. **Channel Naming** - Use clear channel naming conventions
7. **Presence Channels** - Use for user tracking features

## See Also

- [Events](events.md)
- [Queue System](queue.md)
- [API Documentation](api.md)
