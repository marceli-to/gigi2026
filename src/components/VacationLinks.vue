<script setup>
import { ref, onMounted } from 'vue'

const links = ref([])
const userName = ref('Marcel')
const newLinkUrl = ref('')
const newLinkTitle = ref('')
const loading = ref(false)
const error = ref('')

// API URL - assume same host if deployed, but for dev we might need localhost:8000
// If we use relative path '/api.php', we need to proxy it in vite config or serve the built files with PHP.
// For simplicity in dev:
const API_URL = 'http://localhost:8000/api.php'

const fetchLinks = async () => {
  loading.value = true
  try {
    const response = await fetch(API_URL)
    const data = await response.json()
    links.value = data.links
  } catch (e) {
    error.value = 'Failed to fetch links.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

const addLink = async () => {
  if (!newLinkUrl.value) return
  if (!userName.value) {
    alert('Please enter your name first.')
    return
  }

  loading.value = true
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'add_link',
        url: newLinkUrl.value,
        user_name: userName.value,
        title: newLinkTitle.value
      })
    })
    const data = await response.json()
    links.value = data.links
    newLinkUrl.value = ''
    newLinkTitle.value = ''
  } catch (e) {
    alert('Failed to add link.')
  } finally {
    loading.value = false
  }
}

const vote = async (linkId, type) => {
  if (!userName.value) {
    alert('Please enter your name first.')
    return
  }

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'vote',
        link_id: linkId,
        vote_type: type,
        user_name: userName.value
      })
    })
    const data = await response.json()
    links.value = data.links
  } catch (e) {
    alert('Failed to vote.')
  }
}

onMounted(() => {
  fetchLinks()
})
</script>

<template>
  <div class="space-y-6">
    <!-- User Name Select -->
    <div class="bg-white p-4 rounded-lg shadow flex flex-col sm:flex-row items-start sm:items-center gap-4">
      <label class="font-medium text-gray-700 whitespace-nowrap">Name</label>
      <select 
        v-model="userName" 
        class="border border-gray-300 rounded px-1 py-2 w-full sm:w-auto sm:flex-grow focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
      >
        <option value="Marcel">Marcel</option>
        <option value="Balint">Balint</option>
        <option value="Raphael">Raphael</option>
      </select>
    </div>

    <!-- Add Link Form -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h2 class="text-lg font-semibold mb-4">Add</h2>
      <div class="flex flex-col gap-3">
        <input 
          v-model="newLinkUrl" 
          type="url" 
          placeholder="Link" 
          class="border border-gray-300 rounded px-3 py-2 flex-grow focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        <div class="flex flex-col sm:flex-row gap-3">
             <input 
              v-model="newLinkTitle" 
              type="text" 
              placeholder="Location" 
              class="border border-gray-300 rounded px-3 py-2 flex-grow focus:outline-none focus:ring-2 focus:ring-blue-500"
              @keyup.enter="addLink"
            >
        </div>
        <button 
          @click="addLink" 
          class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-black cursor-pointer disabled:opacity-50 transition self-end sm:self-auto w-full sm:w-auto"
          :disabled="loading || !newLinkUrl || !userName"
        >
          Add
        </button>
      </div>
    </div>

    <!-- Links List -->
    <div v-if="loading" class="text-center text-gray-500 animate-pulse">Loading links...</div>
    <div v-else class="space-y-4">
      <div v-for="link in links" :key="link.id" class="bg-white p-4 rounded-lg shadow flex flex-col sm:flex-row justify-between items-start gap-4 hover:shadow-md transition">
        
        <!-- Image -->
        <div v-if="link.image" class="w-full sm:w-48 h-32 shrink-0 rounded-md overflow-hidden bg-gray-100">
            <img :src="link.image" alt="Property Image" class="w-full h-full object-cover">
        </div>

        <div class="overflow-hidden max-w-full w-full">
          <a :href="link.url" target="_blank" class="text-gray-800 hover:underline font-bold text-xl block leading-tight">
            {{ link.title || link.url }}
          </a>
          <div class="text-sm text-gray-500 mt-2 flex items-center gap-2">
            <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600 text-xs">Added by {{ link.added_by }}</span>
          </div>
        </div>
        
        <div class="flex items-center gap-6 shrink-0 w-full sm:w-auto justify-end self-center">
          <div class="flex flex-col items-center group cursor-pointer" @click="vote(link.id, 'up')">
             <button class="p-2 rounded-full group-hover:bg-green-50 text-gray-400 group-hover:text-green-600 transition transform group-active:scale-90">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"/></svg>
             </button>
             <span class="font-bold text-green-700 text-sm">{{ link.upvotes }}</span>
          </div>
          <div class="flex flex-col items-center group cursor-pointer" @click="vote(link.id, 'down')">
             <button class="p-2 rounded-full group-hover:bg-red-50 text-gray-400 group-hover:text-red-600 transition transform group-active:scale-90">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z"/></svg>
             </button>
             <span class="font-bold text-red-700 text-sm">{{ link.downvotes }}</span>
          </div>
        </div>
      </div>
      <div v-if="links.length === 0" class="text-center text-gray-500 py-12 bg-white rounded-lg border-2 border-dashed border-gray-200">
        No vacation homes added yet.<br>Be the first to add a link above!
      </div>
    </div>
  </div>
</template>
