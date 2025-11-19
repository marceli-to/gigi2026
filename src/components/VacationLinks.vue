<script setup>
import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger } from '@/components/ui/dialog'

const links = ref([])
const userName = ref('')
const newLinkUrl = ref('')
const newLinkTitle = ref('')
const loading = ref(false)
const error = ref('')
const showAddDialog = ref(false)
const showUserDialog = ref(false)

// API URL - relative path works in production since api.php is copied to dist/
// For development, run: php -S localhost:5173 -t .
const API_URL = '/api.php'

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
    showAddDialog.value = false
  } catch (e) {
    alert('Failed to add link.')
  } finally {
    loading.value = false
  }
}

const selectUser = (user) => {
  userName.value = user
  showUserDialog.value = false
  fetchLinks()
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
  // Show user selection dialog if no user is selected
  if (!userName.value) {
    showUserDialog.value = true
  } else {
    fetchLinks()
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- User Selection Modal -->
    <Dialog v-model:open="showUserDialog">
      <DialogContent :can-close="false">
        <DialogHeader>
          <DialogTitle>Select Your Name</DialogTitle>
        </DialogHeader>
        <div class="space-y-3 py-4">
          <Button
            @click="selectUser('Marcel')"
            variant="outline"
            class="w-full justify-start text-lg py-6"
          >
            Marcel
          </Button>
          <Button
            @click="selectUser('Balint')"
            variant="outline"
            class="w-full justify-start text-lg py-6"
          >
            Balint
          </Button>
          <Button
            @click="selectUser('Raphael')"
            variant="outline"
            class="w-full justify-start text-lg py-6"
          >
            Raphael
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Add Link Button -->
    <Dialog v-model:open="showAddDialog">
      <DialogTrigger as-child>
        <Button class="w-full" size="lg">
          Add entry
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add entry</DialogTitle>
        </DialogHeader>
        <div class="space-y-4 py-4">
          <div class="flex flex-col space-y-2">
            <Label for="link-url">Link</Label>
            <Input
              id="link-url"
              v-model="newLinkUrl"
              type="url"
              placeholder="https://..."
            />
          </div>
          <div class="flex flex-col space-y-2">
            <Label for="location">Location</Label>
            <Input
              id="location"
              v-model="newLinkTitle"
              type="text"
              placeholder="e.g., Saas Fee"
              @keyup.enter="addLink"
            />
          </div>
        </div>
        <DialogFooter>
          <Button
            @click="addLink"
            :disabled="loading || !newLinkUrl || !userName"
            class="w-full"
          >
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Links List -->
    <div v-if="loading" class="text-center text-muted-foreground animate-pulse">Loading links...</div>
    <div v-else class="space-y-4">
      <Card v-for="link in links" :key="link.id" class="hover:shadow-md transition-shadow">
        <CardContent class="p-4">
          <div class="flex flex-col sm:flex-row justify-between items-start gap-4">

            <!-- Image -->
            <div v-if="link.image" class="w-full sm:w-48 h-32 shrink-0 rounded-md overflow-hidden bg-muted">
              <img :src="link.image" alt="Property Image" class="w-full h-full object-cover">
            </div>

            <div class="overflow-hidden max-w-full w-full">
              <a :href="link.url" target="_blank" class="text-foreground hover:underline font-bold text-xl block leading-tight">
                {{ link.title || link.url }}
              </a>
              <div class="text-sm text-muted-foreground mt-2 flex items-center gap-2">
                <Badge variant="secondary">Added by {{ link.added_by }}</Badge>
              </div>
            </div>

            <div class="flex items-center gap-6 shrink-0 w-full sm:w-auto justify-end self-center">
              <div class="flex flex-col items-center group cursor-pointer" @click="vote(link.id, 'up')">
                <button class="p-2 rounded-full group-hover:bg-green-50 text-muted-foreground group-hover:text-green-600 transition transform group-active:scale-90">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"/></svg>
                </button>
                <span class="font-bold text-green-700 text-sm">{{ link.upvotes }}</span>
              </div>
              <div class="flex flex-col items-center group cursor-pointer" @click="vote(link.id, 'down')">
                <button class="p-2 rounded-full group-hover:bg-red-50 text-muted-foreground group-hover:text-red-600 transition transform group-active:scale-90">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z"/></svg>
                </button>
                <span class="font-bold text-red-700 text-sm">{{ link.downvotes }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="links.length === 0" class="border-dashed border-2">
        <CardContent class="text-center text-muted-foreground py-12">
          No vacation homes added yet.<br>Be the first to add a link above!
        </CardContent>
      </Card>
    </div>
  </div>
</template>
