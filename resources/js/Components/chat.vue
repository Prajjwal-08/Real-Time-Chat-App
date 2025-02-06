<template>
  <div>
    <h2>Chat</h2>
    <ul>
      <li v-for="msg in messages" :key="msg.id">
        {{ msg.message }}
      </li>
    </ul>
    <input v-model="newMessage" @keyup.enter="sendMessage" placeholder="Type a message...">
    <button @click="sendMessage">Send</button>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      messages: [],
      newMessage: '',
    };
  },
  mounted() {
    axios.get('/messages').then(response => {
      this.messages = response.data;  // Fetch existing messages
    });

    window.Echo.private('chat')
      .listen('MessageSent', (e) => {
        console.log("New message received:", e.message); // Debugging
        this.messages.push(e.message);
      });
  },
  methods: {
    sendMessage() {
      if (this.newMessage.trim() === '') return;

      axios.post('/send-message', { message: this.newMessage })
        .then(response => {
          console.log("Message sent:", response.data); // Debugging
          this.newMessage = '';
        })
        .catch(error => {
          console.error("Error sending message:", error);
        });
    }
  }
};
</script>
