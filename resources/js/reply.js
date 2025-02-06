import axios from 'axios';
import Echo from 'laravel-echo';

export default {
  data() {
    return {
      messages: [],
      newMessageContent: '',
    };
  },
  mounted() {
    this.loadMessages();

    // Listen for new messages or replies
    Echo.channel('chat')
      .listen('MessageReplied', (event) => {
        const repliedMessage = this.messages.find(message => message.id === event.reply.parent_id);
        if (repliedMessage) {
          repliedMessage.replies.push(event.reply);
        }
      });
  },
  methods: {
    loadMessages() {
      axios.get('/messages').then(response => {
        this.messages = response.data;
      });
    },
    sendMessage() {
      if (this.newMessageContent.trim() === '') return;

      axios.post('/messages', { content: this.newMessageContent })
        .then(response => {
          this.messages.push(response.data);
          this.newMessageContent = ''; // Reset the input
        });
    },
    showReplyBox(messageId) {
      const message = this.messages.find(msg => msg.id === messageId);
      message.showReplyBox = !message.showReplyBox;
    },
    sendReply(message) {
      if (message.replyContent.trim() === '') return;

      axios.post(`/messages/${message.id}/reply`, { content: message.replyContent })
        .then(response => {
          message.replies.push(response.data);
          message.replyContent = ''; // Clear the reply box
          message.showReplyBox = false; // Hide the reply box
        });
    },
  },
};
