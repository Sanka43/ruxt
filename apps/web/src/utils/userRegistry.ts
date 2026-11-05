import { User } from '../types';

export interface StoredUser extends User {
  password?: string;
}

const REGISTRY_STORAGE_KEY = 'ruxt_users_registry';

export const DEFAULT_ADMIN_USER: StoredUser = {
  id: 'usr_admin_01',
  name: 'RuXt Admin',
  email: 'admin@ruxt.org',
  password: 'Admin@Ruxt2026!',
  avatar: '🛡️',
  role: 'admin',
  createdAt: 1730000000000
};

/**
 * Retrieves all registered users from localStorage, ensuring the default admin is always included.
 */
export function getStoredUsers(): StoredUser[] {
  try {
    const raw = localStorage.getItem(REGISTRY_STORAGE_KEY);
    if (!raw) {
      const initial = [DEFAULT_ADMIN_USER];
      localStorage.setItem(REGISTRY_STORAGE_KEY, JSON.stringify(initial));
      return initial;
    }

    const parsed: StoredUser[] = JSON.parse(raw);
    if (!Array.isArray(parsed) || parsed.length === 0) {
      const initial = [DEFAULT_ADMIN_USER];
      localStorage.setItem(REGISTRY_STORAGE_KEY, JSON.stringify(initial));
      return initial;
    }

    // Ensure root admin always exists
    const hasAdmin = parsed.some((u) => u.email.toLowerCase() === DEFAULT_ADMIN_USER.email.toLowerCase());
    if (!hasAdmin) {
      parsed.unshift(DEFAULT_ADMIN_USER);
      localStorage.setItem(REGISTRY_STORAGE_KEY, JSON.stringify(parsed));
    }

    return parsed;
  } catch (_) {
    return [DEFAULT_ADMIN_USER];
  }
}

/**
 * Saves users list to localStorage.
 */
export function saveStoredUsers(users: StoredUser[]): void {
  try {
    localStorage.setItem(REGISTRY_STORAGE_KEY, JSON.stringify(users));
  } catch (err) {
    console.error('Failed to save user registry to localStorage', err);
  }
}

/**
 * Finds user by email (case-insensitive).
 */
export function findStoredUserByEmail(email: string): StoredUser | undefined {
  const users = getStoredUsers();
  const cleanEmail = email.trim().toLowerCase();
  return users.find((u) => u.email.trim().toLowerCase() === cleanEmail);
}

/**
 * Inserts or updates a user in the local registry.
 */
export function upsertStoredUser(user: Partial<StoredUser> & { email: string; name: string }): StoredUser {
  const users = getStoredUsers();
  const cleanEmail = user.email.trim().toLowerCase();
  const existingIdx = users.findIndex((u) => u.email.trim().toLowerCase() === cleanEmail);

  if (existingIdx >= 0) {
    const updated: StoredUser = {
      ...users[existingIdx],
      ...user,
      email: cleanEmail,
      updatedAt: Date.now()
    };
    users[existingIdx] = updated;
    saveStoredUsers(users);
    return updated;
  } else {
    const newUser: StoredUser = {
      id: user.id || `usr_local_${Date.now()}`,
      name: user.name.trim(),
      email: cleanEmail,
      password: user.password,
      avatar: user.avatar || user.name.trim().charAt(0).toUpperCase() || '👤',
      role: user.role || 'user',
      createdAt: user.createdAt || Date.now()
    };
    users.push(newUser);
    saveStoredUsers(users);
    return newUser;
  }
}

/**
 * Updates an existing user by ID.
 */
export function updateStoredUser(id: string, updates: Partial<StoredUser>): StoredUser | null {
  const users = getStoredUsers();
  const idx = users.findIndex((u) => u.id === id);
  if (idx === -1) return null;

  users[idx] = {
    ...users[idx],
    ...updates,
    updatedAt: Date.now()
  };
  saveStoredUsers(users);
  return users[idx];
}

/**
 * Updates user password by ID.
 */
export function updateStoredUserPassword(id: string, newPassword: string): boolean {
  const users = getStoredUsers();
  const idx = users.findIndex((u) => u.id === id);
  if (idx === -1) return false;

  users[idx].password = newPassword;
  users[idx].updatedAt = Date.now();
  saveStoredUsers(users);
  return true;
}

/**
 * Deletes user by ID (prevents root admin deletion).
 */
export function deleteStoredUser(id: string): boolean {
  const users = getStoredUsers();
  const target = users.find((u) => u.id === id);
  if (!target || target.email.toLowerCase() === DEFAULT_ADMIN_USER.email.toLowerCase()) {
    return false;
  }

  const filtered = users.filter((u) => u.id !== id);
  saveStoredUsers(filtered);
  return true;
}

/**
 * Authenticates user offline against the local registry.
 */
export function authenticateLocalUser(email: string, password: string): { user: User; token: string } {
  const cleanEmail = email.trim().toLowerCase();
  const user = findStoredUserByEmail(cleanEmail);

  if (!user) {
    throw new Error('No account found with this email address. Please register first.');
  }

  if (user.password && user.password !== password) {
    throw new Error('Incorrect password. Please verify and try again.');
  }

  const token = `ruxt_session_${user.id}_${Date.now()}`;
  const safeUser: User = {
    id: user.id,
    name: user.name,
    email: user.email,
    avatar: user.avatar,
    role: user.role,
    createdAt: user.createdAt,
    updatedAt: user.updatedAt
  };

  return { user: safeUser, token };
}
