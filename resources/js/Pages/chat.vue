<template>

    <Head title="chat" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Personal Chat Room
            </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 row grid grid-cols-12">
                <!-- Users List -->
                <div class="col-span-4 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold">Users</h3>
                    <div class="bg-gray-100 p-4 mt-4">
                        <ul>
                            <li v-for="userItem in users" :key="userItem.id"
                                class="py-1 cursor-pointer hover:bg-gray-200 p-2 rounded flex items-center"
                                @click="selectUser(userItem)">
                                <img :src="'/storage/' + userItem.profile_image" alt="User profile_image"
                                    class="w-10 h-10 rounded-full mr-2">
                                {{ userItem.name }}
                            </li>


                        </ul>
                    </div>
                </div>
                <li v-for="user in allUsers" :key="user.id">

              <strong>{{ user.name }}</strong>
              <p>Email: {{ user.email }}</p>
              <p>Joined: {{ user.created_at }}</p>
              <!-- If you have a profile picture and bio -->
              <div v-if="user.profile_picture">
                <img :src="user.profile_picture" alt="Profile Picture" />
              </div>
              <p v-if="user.bio">Bio: {{ user.bio }}</p>

          </li>
                <!-- Chat Box -->
                <div v-if="selectedUser" class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6 col-span-8">
                    <h3 class="text-lg font-semibold flex items-center">
                        <img :src="'/storage/' + selectedUser.profile_image" alt="User profile_image"
                            class="w-8 h-8 rounded-full mr-2">
                        Chat with {{ selectedUser.name }}
                    </h3>

                    <!-- Chat Messages -->
                    <div ref="messageContainer" class="h-60 overflow-y-auto border p-4 bg-gray-100 rounded mt-4">
                        <div v-for="msg in filteredMessages" :key="msg.id"
                            class="mb-2 flex justify-between items-start">
                            <div class="p-2 rounded-lg w-fit" :class="{
                                'bg-blue-500 text-white self-end': msg.user.id === currentUser.id,
                                'bg-gray-300 text-black self-start': msg.user.id !== currentUser.id
                            }">
                                <strong>{{ msg.user.name }}:</strong> {{ msg.message }}

                                <div v-if="msg.replyTo" class="text-xs mt-1 p-1 border-l-2 border-gray-500">
                                    Replying to: <strong>{{ msg.replyTo.user.name }}</strong> - {{ msg.replyTo.message
                                    }}
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="setReply(msg)" class="text-blue-500 text-sm">Reply</button>
                                <button v-if="msg.user.id === currentUser.id" @click="deleteMessage(msg.id)"
                                    class="text-red-500 text-sm">Delete</button>
                            </div>
                        </div>
                    </div>

                    <!-- Reply Preview -->
                    <div v-if="replyToMessage" class="mt-2 p-2 border-l-4 border-blue-500 bg-blue-100 text-sm rounded">
                        Replying to: <strong>{{ replyToMessage.user.name }}</strong> - {{ replyToMessage.message }}
                        <button @click="clearReply" class="text-red-500 text-xs ml-2">Cancel</button>
                    </div>

                    <!-- Message Input -->
                    <div class="message-input flex items-center mt-2">
                        <textarea v-model="newMessage" @keyup.enter="sendMessage" placeholder="Type your message..."
                            class="flex-1 border rounded p-2"></textarea>
                        <button @click="sendMessage" class="ml-2 bg-blue-500 text-white px-4 py-2 rounded">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import Echo from 'laravel-echo';
import { usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
const users = ref([]);
const selectedUser = ref(null);
const messages = ref([]);
const newMessage = ref('');
const replyToMessage = ref(null);
const currentUser = user;
const messageContainer = ref(null);

const fetchAllUsers = async () => {
    try {
        const response = await axios.get('/users');  // Adjust the endpoint as per your API
        allUsers.value = response.data.users;  // Store the fetched users' data
    } catch (error) {
        console.error('Error fetching users:', error);
    }
};

const fetchMessages = async () => {
    try {
        const response = await axios.get('/messages');
        messages.value = response.data.messages;
        users.value = response.data.users;
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
};

const selectUser = (userItem) => {
    selectedUser.value = userItem;
};

const filteredMessages = computed(() => {
    return messages.value.filter(msg => msg.user.id === selecftedUser.value?.id || msg.receiver_id === selectedUser.value?.id);
});

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    const formData = new FormData();
    formData.append('message', newMessage.value);
    formData.append('receiver_id', selectedUser.value.id);
    if (replyToMessage.value) formData.append('reply_to', replyToMessage.value.id);

    try {
        await axios.post('/send-message', formData);
        newMessage.value = '';
        replyToMessage.value = null;
        fetchMessages();
    } catch (error) {
        console.error('Error sending message:', error);
    }
};

onMounted(() => {
    fetchMessages();
    fetchAllUsers();
    if (user?.id) {
        window.Echo.private(`chat.${user.id}`)
            .listen('MessageSent', (event) => {
                if (selectedUser.value && event.message.user.id === selectedUser.value.id) {
                    messages.value.push(event.message);
                }
            });
    }
});

const scrollToBottom = () => {
    if (messageContainer.value) {
        messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
    }
};

const setReply = (msg) => {
    replyToMessage.value = msg;
};

const clearReply = () => {
    replyToMessage.value = null;
};

const deleteMessage = async (messageId) => {
    try {
        await axios.post(`/delete-message/${messageId}`);
        fetchMessages();
    } catch (error) {
        console.error('Error deleting message:', error);
    }
};

watch(messages, () => {
    scrollToBottom();
});
</script>
