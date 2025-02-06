
import { ref, watch, onMounted } from 'vue';
import * as pdfjsLib from 'pdfjs-dist';

export default {
    data() {
        return {
            messages: [],  // Array to hold messages
            newReply: '',  // New reply input
            replyToMessage: null,  // Message to which the user wants to reply
            user: { id: 1, name: 'John Doe' },  // Example user data
        };
    },
    methods: {
        // Renders the first page of the PDF onto a canvas
        async renderPDF(file, canvasId) {
            const loadingTask = pdfjsLib.getDocument(file);
            loadingTask.promise.then(pdf => {
                pdf.getPage(1).then(page => {
                    const scale = 1.5;
                    const viewport = page.getViewport({ scale });

                    const canvas = document.getElementById(canvasId);
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport,
                    };

                    page.render(renderContext);
                });
            }).catch(error => {
                console.error('Error loading PDF: ', error);
            });
        },

        // Check if the file is a PDF and render the preview
        handleFilePreview(msg) {
            if (msg.file.match(/\.pdf$/i)) {
                this.$nextTick(() => {
                    this.renderPDF('/storage/' + msg.file, 'pdf-canvas-' + msg.id);
                });
            }
        },

        // Function to handle deleting a message
        deleteMessage(msgId) {
            // Add your delete message logic here
        },

        // Function to send a reply to a message
        sendReply(msgId) {
            // Add your send reply logic here
        },
    },

    watch: {
        // Watch for new messages and trigger PDF rendering if needed
        messages(newMessages) {
            newMessages.forEach(msg => {
                if (msg.file) {
                    this.handleFilePreview(msg);
                }
            });
        }
    },

    mounted() {
        // You can simulate loading messages here or fetch from your API
        this.messages = [
            // Example message with a PDF attachment
            { id: 1, user: { id: 2, name: 'Alice' }, message: 'Here is a PDF', file: 'example.pdf', replies: [], deleted_at: null },
            { id: 2, user: { id: 1, name: 'John' }, message: 'This is a reply', file: 'image.jpg', replies: [], deleted_at: null },
        ];
    }
};

