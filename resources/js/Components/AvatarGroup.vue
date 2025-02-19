<template>
  <div class="flex -space-x-2 overflow-hidden">
    <div
      v-for="member in members"
      :key="member.id"
      class="inline-flex items-center justify-center h-8 w-8 rounded-full border-2 border-white bg-gray-100"
      :title="getMemberName(member)"
    >
      <span class="text-sm font-medium text-gray-600">
        {{ getInitials(member) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Member {
  id: number;
  user_id?: number;
  first_name?: string;
  last_name?: string;
  role: string;
  user?: {
    name: string;
  };
}

interface Props {
  members: Member[];
}

const props = defineProps<Props>();

const getMemberName = (member: Member): string => {
  if (member.user_id && member.user) {
    return `${member.user.name} - ${formatRole(member.role)}`;
  }
  if (member.first_name && member.last_name) {
    return `${member.first_name} ${member.last_name} - ${formatRole(member.role)}`;
  }
  return 'Membre sans nom';
}

const getInitials = (member: Member): string => {
  if (member.user_id && member.user) {
    return member.user.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  }
  if (member.first_name && member.last_name) {
    return (member.first_name[0] + member.last_name[0]).toUpperCase();
  }
  return 'XX';
}

const formatRole = (role: string): string => {
  const roles: { [key: string]: string } = {
    'president': 'Président',
    'vice_president': 'Vice-président',
    'treasurer': 'Trésorier',
    'secretary': 'Secrétaire',
    'member': 'Membre'
  }
  return roles[role] || role;
}
</script> 