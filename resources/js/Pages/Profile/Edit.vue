<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
})
</script>

<template>
    <Head title="Mon profil" />
    <hr>

    <AuthenticatedLayout>
        <div class="profile-page">
            <div class="profile-wrapper">
                <div class="info-section">
                    <h1 class="profile-title text-center">✏️ Mon profil</h1>
                    <!-- Forms layout: photo + two forms -->
                    <div class="forms-container">
                        <div class="photo-section-left">
                            <img
                                :src="$page.props.auth.user.picture_profil || '/images/default-avatar.png'"
                                alt="Photo de profil"
                                class="profile-photo-small"
                            />
                        </div>
                        <div class="divider"></div>
                        <div class="left-form">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>
                        <div class="divider"></div>
                        <div class="right-form">
                            <UpdatePasswordForm />
                        </div>
                    </div>
                    <div class="mt-8 w-full">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.profile-page {
    background: #f9f5f2;
    padding: 2rem;
    min-height: 100vh;
}
.profile-wrapper {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    display: flex;
    justify-content: center;
}
.info-section {
    width: 100%;
    max-width: 900px;
}
.profile-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #0f766e;
    margin-bottom: 1rem;
}
.forms-container {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 0.5rem;
}
.photo-section-left {
    flex: 0 0 auto;
    margin-top: 1.5rem;
}
.profile-photo-small {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #0d9488;
}
.left-form,
.right-form {
    flex: 1;
}
.divider {
    width: 1px;
    background: #e5e7eb;
    margin: 0;
}
</style>
