<template>
  <div>
    <h1>Users Management</h1>
    <div v-if="users && users.data">
      <div v-for="user in users.data" :key="user.id">
        <p>{{ user.name }} - {{ user.email }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  users: Object
})
</script>

