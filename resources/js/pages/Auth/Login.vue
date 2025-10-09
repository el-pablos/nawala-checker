<template>
  <div>
    <h1>Login</h1>
    <form @submit.prevent="submit">
      <div>
        <label for="email">Email</label>
        <input id="email" v-model="form.email" type="email" required />
      </div>
      <div>
        <label for="password">Password</label>
        <input id="password" v-model="form.password" type="password" required />
      </div>
      <div>
        <label>
          <input v-model="form.remember" type="checkbox" />
          Remember me
        </label>
      </div>
      <button type="submit">Login</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const form = ref({
  email: '',
  password: '',
  remember: false
})

const submit = () => {
  router.post('/login', form.value)
}
</script>

