<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const imagePreview = ref(user.profile_image ? `/storage/${user.profile_image}` : null);

// Create the form data object, including current user information
const form = useForm({
    name: user.name,
    email: user.email,
    profile_image: null,
});

const previewImage = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.profile_image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.profile_image = null;
    imagePreview.value = user.profile_image ? `/storage/${user.profile_image}` : null;
};

import axios from 'axios';

const submitForm = () => {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('email', form.email);

    if (form.profile_image) {
        formData.append('profile_image', form.profile_image);
    }

    // Log the FormData contents
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }

    axios.post(route('profile.update'), formData)
        .then(response => {
            console.log('Successfully updated profile:', response.data);
            form.reset();
        })
        .catch(error => {
            console.error('Error updating profile:', error);
        });
};

</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="submitForm" class="mt-6 space-y-6" enctype="multipart/form-data">
            <!-- Name Input -->
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Email Input -->
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Profile Image Input -->
            <div>
                <InputLabel for="profile_image" value="Profile Image" />

                <!-- Preview Image Section -->
                <div v-if="imagePreview || user.profile_image" class="mt-2">
    <img
        :src="imagePreview || 'public/storage/' + user.profile_image"
        alt="Profile Image Preview"
        class="w-20 h-20 rounded-full object-cover"
    />
                    <!-- Add remove button -->
                    <button
                        type="button"
                        @click="removeImage"
                        class="mt-2 text-sm text-red-600 hover:underline"
                    >
                        Remove Image
                    </button>
                </div>

                <!-- File Input for Profile Image -->
                <input
                    type="file"
                    id="profile_image"
                    class="mt-2 block w-full"
                    @change="previewImage"
                />
                <InputError class="mt-2" :message="form.errors.profile_image" />
            </div>

            <!-- Email Verification Section -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <!-- Save Button and Status Message -->
            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
