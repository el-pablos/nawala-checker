<template>
  <div>
    <h1>User Details</h1>
    <div v-if="user">
      <p>Name: {{ user.name }}</p>
      <p>Email: {{ user.email }}</p>
      <p>Admin: {{ user.is_admin ? 'Yes' : 'No' }}</p>
      <p>Active: {{ user.is_active ? 'Yes' : 'No' }}</p>
    </div>
    <div v-if="targets">
      <h2>Targets</h2>
      <p>Total: {{ targets.length }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  user: Object,
  targets: Array
})
</script>

