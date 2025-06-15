<template>
    <AuthenticatedLayout>
        <div class="page-all">
            <h1 class="text-3xl font-bold text-teal-700 mb-6 flex items-center gap-3">
                Liste des utilisateurs
                <span class="text-sm font-medium bg-teal-100 text-teal-800 px-3 py-1 rounded-full shadow-sm">
          {{ totalUsers }} inscrits
        </span>
            </h1>

            <input
                v-model="search"
                type="text"
                placeholder="🔍 Rechercher un pseudo, prénom, nom ou email..."
                class="mb-6 px-4 py-2 border border-gray-300 rounded-lg shadow-sm w-full focus:ring-teal-500 focus:border-teal-500"
            />

            <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200">
                <table class="min-w-full bg-white text-sm text-gray-800 rounded-xl">
                    <thead class="bg-teal-50 text-left text-teal-800">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Pseudo</th>
                        <th class="px-4 py-3 font-semibold">Prénom</th>
                        <th class="px-4 py-3 font-semibold">Nom</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">Rôle</th>
                        <th class="px-4 py-3 font-semibold">Dernière connexion</th>
                        <th v-if="props.userRole.includes('Admin') || props.userRole.includes('Super-admin')" class="px-4 py-3 font-semibold">Activation</th>
                        <th v-if="props.userRole.includes('Super-admin')" class="px-4 py-3 font-semibold">Anonymat</th>
                        <th class="px-4 py-3 font-semibold">Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="hover:bg-gray-50 transition"
                    >
                        <td class="px-4 py-2">
                            <Link
                                v-if="!user.anonyme"
                                :href="route('users.show', user.id)"
                                class="text-blue-600 hover:underline font-medium"
                                v-html="highlightMatch(user.pseudo)"
                            />
                            <span v-else v-html="highlightMatch(user.pseudo)"></span>
                            <span v-if="user.id === currentUserId" class="text-sm text-teal-500 font-semibold"> (Moi)</span>
                        </td>
                        <td class="px-4 py-2" v-html="highlightMatch(user.first_name)"></td>
                        <td class="px-4 py-2" v-html="highlightMatch(user.last_name)"></td>
                        <td class="px-4 py-2" v-html="highlightMatch(user.email)"></td>
                        <td class="px-4 py-2 text-center">
                            <div v-if="props.userRole.includes('Super-admin')">
                                <select
                                    :value="user.roles[0]?.name"
                                    @change="(event) => updateRole(user, event)"
                                    class="px-2 py-1 border border-gray-300 rounded focus:ring-teal-500 focus:border-teal-500"
                                    :disabled="cannotEditRole(user)"
                                >
                                    <option value="User">User</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Super-admin">Super-admin</option>
                                </select>
                            </div>
                            <div v-else>
                  <span
                      v-for="role in user.roles"
                      :key="role.id"
                      class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-semibold"
                  >
                    {{ role.name }}
                  </span>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-center">
                            {{ user.last_login ? new Date(user.last_login).toLocaleString() : 'Jamais' }}
                        </td>
                        <td v-if="props.userRole.includes('Admin') || props.userRole.includes('Super-admin')" class="px-4 py-2 text-center">
                            <button
                                v-if="!user.roles.some(r => ['Admin', 'Super-admin'].includes(r.name)) && !user.anonyme"
                                @click="toggleActivation(user)"
                                class="text-white bg-teal-500 hover:bg-teal-600 px-3 py-1 rounded-full text-xs font-medium shadow-sm"
                            >
                                {{ user.is_actif ? 'Désactiver' : 'Activer' }}
                            </button>
                            <span v-else class="text-gray-400 italic text-sm">Non autorisé</span>
                        </td>
                        <td v-if="props.userRole.includes('Super-admin')" class="px-4 py-2 text-center">
                            <button
                                v-if="!user.roles.some(r => ['Admin', 'Super-admin'].includes(r.name)) && !user.anonyme"
                                @click="anonymizeUser(user)"
                                class="text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded-full text-xs font-medium shadow-sm"
                            >
                                Anonymiser
                            </button>
                            <span v-else-if="user.anonyme" class="text-gray-400 italic text-sm">Anonymisé</span>
                            <span v-else class="text-gray-400 italic text-sm">Non autorisé</span>
                        </td>
                        <td class="px-4 py-2 text-center font-semibold">
                <span :class="user.is_actif ? 'text-green-600' : 'text-red-600'">
                  {{ user.is_actif ? 'Actif' : 'Inactif' }}
                </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-between items-center">
                <button
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded shadow-sm"
                    :disabled="!props.users.prev_page_url"
                    @click="goToPage(props.users.prev_page_url)"
                >
                    ⬅️ Précédent
                </button>
                <span class="text-sm text-gray-500">
          Page {{ props.users.current_page }} sur {{ props.users.last_page }}
        </span>
                <button
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded shadow-sm"
                    :disabled="!props.users.next_page_url"
                    @click="goToPage(props.users.next_page_url)"
                >
                    Suivant ➡️
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-all {
    background: #fdfcfa;
    padding: 30px 20px;
    border-radius: 16px;
}

mark {
    background-color: #fef08a;
    padding: 0 2px;
    border-radius: 3px;
    font-weight: 600;
}
</style>
