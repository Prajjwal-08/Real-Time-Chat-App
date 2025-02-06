<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Echo from 'laravel-echo';
import $ from 'jquery';
import '@fortawesome/fontawesome-free/css/all.css';
import chatScript from '../reply.js'
import pdf from '@/pdf.js';


const user = usePage().props.auth.user; // Get authenticated user
const messages = ref([]); // Store messages
const newMessage = ref(''); // Input field for new messages
const replyToMessage = ref(null); // Store the message being replied to
const newReply = ref(''); // Store new reply
const media = ref(null); // Store uploaded media
const users = ref([]);
const previewUrl = ref(null); // Preview URL for icon or image
const isPDF = ref(false); // Track if the uploaded file is PDF
// Fetch messages from backend
const fetchMessages = async () => {
    $.ajax({
        url: '/messages',
        method: 'get',
        success: (response) => {
            messages.value = response.messages;
            users.value = response.users;
        },
        error: (xhr, status, error) => {
            console.error('error fetching messages', error);
        }
    });
};

// Send a new message
const uploadProgress = ref(0);
const uploading = ref(false);
const mediaPreview = ref(null);

// const sendMessage = async () => {
//     if (!newMessage.value.trim() && !media.value) return;

//     const formData = new FormData();
//     formData.append('message', newMessage.value);
//     if (media.value) {
//         formData.append('file', media.value);
//     }
//     if (replyToMessage.value) formData.append('reply_to', replyToMessage.value.id);

//     uploading.value = true; // Show progress
//     uploadProgress.value = 0;

//     try {
//         await axios.post('/send-message', formData, {
//             headers: { 'Content-Type': 'multipart/form-data' },
//             onUploadProgress: (progressEvent) => {
//                 uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
//             }
//         });

//         newMessage.value = ''; // Clear input field
//         media.value = null; // Reset media input
//         replyToMessage.value = null;
//         uploading.value = false;
//         fetchMessages(); // Reload messages
//     } catch (error) {
//         console.error('Error sending message:', error);
//         uploading.value = false;
//     }
// };

const renderPDFPreview = (file) => {
    const fileReader = new FileReader();
    fileReader.onload = () => {
        const pdfData = new Uint8Array(fileReader.result);
        pdfjsLib.getDocument(pdfData).promise.then(pdf => {
            pdf.getPage(1).then(page => {
                const canvas = document.createElement("canvas");
                const context = canvas.getContext("2d");
                const viewport = page.getViewport({ scale: 0.5 }); // Scale down to fit the preview
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                page.render({
                    canvasContext: context,
                    viewport: viewport,
                }).promise.then(() => {
                    previewUrl.value = canvas.toDataURL(); // Convert canvas to data URL and set it as preview
                });
            });
        });
    };
    fileReader.readAsArrayBuffer(file); // Read PDF as ArrayBuffer
};
const sendMessage = async () => {
    if (!newMessage.value.trim() && !media.value) {
        'no media wgera found'
        return; // Ensure at least message or media is provided
    }
    'media wgera found'

    const formData = new FormData();
    formData.append('message', newMessage.value);

    if (media.value) {
        formData.append('file', media.value, media.value.name); // Ensure correct file handling
    }

    if (replyToMessage.value) {
        formData.append('reply_to', replyToMessage.value.id);
    }

    try {
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }
        await axios.post('/send-message', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        // Clear input fields after sending
        newMessage.value = '';
        media.value = null;
        mediaPreview.value = null;
        replyToMessage.value = null;
        fetchMessages(); // Reload messages
    } catch (error) {
        console.error('Error sending message:', error);
    }
};

// Listen for real-time messages using Pusher
onMounted(() => {
    fetchMessages(); // Load existing messages

    if (user && user.id) {
        // Initialize Pusher/Echo for user-specific channel
        window.Echo.channel(`chat.${user.id}`)
            .listen('MessageSent', (event) => {
                messages.value.push(event.message); // Append new message
            });
    } else {
        console.error("User is not authenticated.");
    }
});

// Send a reply to an original message
const sendReply = async (originalMessageId) => {
    if (!newReply.value.trim()) return;

    try {
        // Send the reply as a new message, associating it with the original message
        await axios.post('/send-message', {
            message: newReply.value,
            reply_to: originalMessageId,
        });

        // Reset the reply state
        newReply.value = '';
        replyToMessage.value = null;
        fetchMessages(); // Reload messages after sending the reply
    } catch (error) {
        console.error('Error sending reply:', error);
    }
};
// Set reply
const setReply = (message) => {
    replyToMessage.value = message;
};

// Cancel reply
const cancelReply = () => {
    replyToMessage.value = null;
};
// Delete a message
const deleteMessage = async (messageId) => {
    try {
        await axios.post(`/delete-message/${messageId}`);
        fetchMessages(); // Reload messages after deletion
    } catch (error) {
        console.error('Error deleting message:', error);
    }
};
fetchMessages();

const handleFileUpload = (event) => {

    const file = event.target.files[0];
    if (file) {
        media.value = file;
        isPDF.value = file.type === "application/pdf"; // Check if the file is PDF

        // If it's a PDF, create preview
        if (isPDF.value) {
            renderPDFPreview(file); // Render PDF thumbnail
        } else {
            previewUrl.value = URL.createObjectURL(file); // Show image preview
        }
    }
};
const removeFile = () => {
    media.value = null;
    previewUrl.value = null;
};

</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Group Chat Dashboard
            </h2>
        </template>

        <div class="py-12">

            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- <div class="col-span-4 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold">Chat Room</h3>
                    <div class="bg-gray-100 p-4 mt-4">
                        <ul>
                            <li v-for="user in users" :key="user.id" class="py-1">
                                <i class="fas fa-user-circle text-blue-500"></i> {{ user.name }}
                            </li>
                        </ul>
                    </div>
                </div> -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6 col-span-8">
                    <h3 class="text-lg font-semibold">Group Chat Room</h3>

                    <!-- Chat Messages -->
                    <div class="h-60 overflow-y-auto border p-4 bg-gray-100 rounded mt-4">
                        <div v-for="msg in messages" :key="msg.id" class="mb-2 message-wrapper">
                            <!-- Message -->
                            <div :class="{ 'sent-message': msg.user.id === user.id, 'received-message': msg.user.id !== user.id }"
                                class="message">
                                <!-- Deleted Message -->
                                <div v-if="msg.deleted_at">
                                    <em v-if="msg.user.id === user.id">
                                        <i class="fa-solid fa-ban"></i> You deleted this message.
                                    </em>
                                    <em v-else>
                                        <strong>{{ msg.user.name }}:</strong> <br>
                                        <i class="fa-solid fa-ban"></i> This message was deleted.
                                    </em>
                                </div>

                                <!-- Normal Message -->
                                <div v-else>
                                    <strong v-if="msg.user.id != user.id">{{ msg.user.name }}: <br></strong>
                                    {{ msg.message }}

                                    <!-- File Attachment -->
                                    <div v-if="msg.file">
                                        <div v-if="msg.file.match(/\.(jpg|jpeg|png|gif)$/i)">
                                            <img :src="'/storage/' + msg.file" class="message-media" />
                                        </div>

                                        <!-- PDF Preview -->
                                        <div v-else-if="msg.file.match(/\.pdf$/i)">
                                            <div class="pdf-preview-container">
                                                <canvas :id="'pdf-canvas-' + msg.id" class="pdf-preview"></canvas>
                                            </div>
                                            <a :href="'/storage/' + msg.file" target="_blank">
                                                <i class="fas fa-paperclip"></i> View PDF
                                            </a>
                                        </div>


                                        <!-- Other Attachments -->
                                        <a v-else :href="'/storage/' + msg.file" target="_blank">
                                            <i class="fas fa-paperclip"></i> View Attachment
                                        </a>
                                    </div>

                                    <!-- Show Replies -->
                                    <div v-if="msg.replies.length" class="ml-5 mt-2">
                                        <div v-for="reply in msg.replies" :key="reply.id" class="reply-message">
                                            <strong>{{ reply.user.name }}:</strong> {{ reply.message }}
                                        </div>
                                    </div>

                                    <!-- Reply Button -->
                                    <button @click="replyToMessage = msg" class="text-blue-500 ml-2">Reply</button>
                                </div>
                            </div>

                            <!-- Reply Input -->
                            <div v-if="replyToMessage && replyToMessage.id === msg.id" class="reply-input">
                                <textarea v-model="newReply" placeholder="Type your reply..."></textarea>
                                <button @click="sendReply(msg.id)">Send Reply</button>
                            </div>
                            <!-- PDF Thumbnail for PDFs -->
                            <div v-if="isPDF" class="pdf-preview">
                                <img :src="previewUrl" alt="PDF Preview" width="80px" />
                                <span>PDF File</span>
                            </div>
                            <!-- Image Preview for Images -->
                            <div v-else>
                                <img :src="previewUrl" alt="File Preview" width="100px" />
                            </div>

                            <!-- Delete Button -->
                            <!-- Delete Button -->
                            <span v-if="msg.user.id === user.id && !msg.deleted_at" class="delete-button">
                                <button @click="deleteMessage(msg.id)"><i class="fas fa-trash-alt"></i></button>
                            </span>

                        </div>
                    </div>


                    <!-- Message Input -->
                    <div class="message-input">
                        <!-- File input with file icon -->
                        <!-- File Upload Indicator -->
                        <div v-if="uploading" class="upload-progress">
                            Uploading... {{ uploadProgress }}%
                        </div>

                        <!-- File Input -->
                        <label for="media-file" class="file-input-label">
                            <i class="fas fa-paperclip"></i>
                        </label>
                        <input id="media-file" type="file" @change="handleFileUpload" style="display: none;" />
                        <!-- File Preview -->
                        <div v-if="mediaPreview" class="file-preview">
                            <template v-if="media.type.startsWith('image/')">
                                <img :src="mediaPreview" alt="Preview" class="preview-img" />
                            </template>
                            <template v-else>
                                <i class="fas fa-file-alt"></i> {{ media.name }}
                            </template>

                            <!-- Remove File Button -->
                            <button @click="removeFile" class="remove-file"><i class="fas fa-times"></i></button>
                        </div>

                        <textarea v-model="newMessage" @keyup.enter="sendMessage"
                            placeholder="Type your message..."></textarea>

                        <!-- Send button with arrow icon -->
                        <button @click="sendMessage" class="send-button">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Styling for messages and media */
.message-wrapper {
    display: flex;
    flex-direction: column;
}

.sent-message {
    text-align: right;
    background-color: #d1f7c4;
    /* Light green for sent messages */
    padding: 10px;
    border-radius: 8px;
    margin-left: auto;
}

.received-message {
    text-align: left;
    background-color: #f0f0f0;
    /* Light grey for received messages */
    padding: 10px;
    border-radius: 8px;
    margin-right: auto;
}

.message-media {
    max-width: 100%;
    max-height: 200px;
    margin-top: 5px;
}

.message-input {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.message-input label {
    cursor: pointer;
}

.message-input textarea {
    width: 100%;
    padding: 8px;
    border-radius: 4px;
    flex-grow: 1;
    border: none;
    box-shadow: none;
}

.message-input input[type="file"] {
    border: none;
    display: none;
}

.message-input button {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.message-input button:hover {
    background-color: #45a049;
}

.file-input-label i,
.send-button i {
    font-size: 20px;
}

.delete-button {
    margin-top: 5px;
    text-align: right;
}

.delete-button button {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

.delete-button button:hover {
    background-color: #e53935;
}



.reply-quote {
    background: #e0e0e0;
    padding: 5px;
    border-left: 3px solid #888;
    margin-bottom: 5px;
}

.reply-preview {
    background: #f8f8f8;
    padding: 5px;
    border: 1px solid #ddd;
}
</style>
