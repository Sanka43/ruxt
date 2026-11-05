import crypto from 'crypto';
import { db } from '../db/index.js';
import { usersTable, sessionsTable } from '../db/schema.js';
import { eq } from 'drizzle-orm';
import { User } from '../domain/types.js';

const SESSION_DURATION_MS = 30 * 24 * 60 * 60 * 1000; // 30 days

export class AuthService {
  private hashPassword(password: string): string {
    const salt = crypto.randomBytes(16).toString('hex');
    const hash = crypto.scryptSync(password, salt, 64).toString('hex');
    return `${salt}:${hash}`;
  }

  private verifyPassword(password: string, storedHash: string): boolean {
    const [salt, hash] = storedHash.split(':');
    if (!salt || !hash) return false;
    const computedHash = crypto.scryptSync(password, salt, 64).toString('hex');
    try {
      return crypto.timingSafeEqual(Buffer.from(hash, 'hex'), Buffer.from(computedHash, 'hex'));
    } catch {
      return false;
    }
  }

  private generateToken(): string {
    return crypto.randomBytes(32).toString('hex');
  }

  async ensureAdminUser(): Promise<void> {
    const allUsers = await db.select().from(usersTable);
    if (allUsers.length === 0) {
      const adminId = 'usr_admin_01';
      const adminEmail = 'admin@ruxt.org';
      const defaultPassword = 'Admin@Ruxt2026!';
      const passwordHash = this.hashPassword(defaultPassword);

      await db.insert(usersTable).values({
        id: adminId,
        name: 'RuXt Admin',
        email: adminEmail,
        passwordHash,
        avatar: '🛡️',
        role: 'admin',
        createdAt: Date.now(),
        updatedAt: Date.now()
      });

      console.log('------------------------------------------------------');
      console.log('🔑 Initial Default Admin Account Provisioned:');
      console.log(`   Email:    ${adminEmail}`);
      console.log(`   Password: ${defaultPassword}`);
      console.log('------------------------------------------------------');
    }
  }

  async register(name: string, email: string, password: string): Promise<{ user: User; token: string }> {
    const cleanEmail = email.trim().toLowerCase();
    const cleanName = name.trim();

    if (!cleanEmail || !cleanEmail.includes('@')) {
      throw new Error('A valid email address is required.');
    }
    if (!cleanName || cleanName.length < 2) {
      throw new Error('Full name must be at least 2 characters.');
    }
    if (!password || password.length < 6) {
      throw new Error('Password must be at least 6 characters.');
    }

    const existing = await db.select().from(usersTable).where(eq(usersTable.email, cleanEmail));
    if (existing.length > 0) {
      throw new Error('An account with this email address already exists.');
    }

    const userId = `usr_${Date.now()}_${crypto.randomBytes(4).toString('hex')}`;
    const passwordHash = this.hashPassword(password);
    const avatar = cleanName.charAt(0).toUpperCase();

    const newUser = {
      id: userId,
      name: cleanName,
      email: cleanEmail,
      passwordHash,
      avatar,
      role: 'user' as const,
      createdAt: Date.now(),
      updatedAt: Date.now()
    };

    await db.insert(usersTable).values(newUser);

    const token = this.generateToken();
    await db.insert(sessionsTable).values({
      token,
      userId,
      expiresAt: Date.now() + SESSION_DURATION_MS,
      createdAt: Date.now()
    });

    return {
      user: {
        id: newUser.id,
        name: newUser.name,
        email: newUser.email,
        avatar: newUser.avatar,
        role: newUser.role,
        createdAt: newUser.createdAt
      },
      token
    };
  }

  async login(email: string, password: string): Promise<{ user: User; token: string }> {
    const cleanEmail = email.trim().toLowerCase();
    if (!cleanEmail || !password) {
      throw new Error('Email and password are required.');
    }

    const rows = await db.select().from(usersTable).where(eq(usersTable.email, cleanEmail));
    if (rows.length === 0) {
      throw new Error('Invalid email or password.');
    }

    const user = rows[0];
    const isValid = this.verifyPassword(password, user.passwordHash);
    if (!isValid) {
      throw new Error('Invalid email or password.');
    }

    const token = this.generateToken();
    await db.insert(sessionsTable).values({
      token,
      userId: user.id,
      expiresAt: Date.now() + SESSION_DURATION_MS,
      createdAt: Date.now()
    });

    return {
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        avatar: user.avatar,
        role: user.role as 'admin' | 'user',
        createdAt: user.createdAt,
        updatedAt: user.updatedAt
      },
      token
    };
  }

  async getUserByToken(token: string): Promise<User | null> {
    if (!token) return null;

    const sessionRows = await db.select().from(sessionsTable).where(eq(sessionsTable.token, token));
    if (sessionRows.length === 0) return null;

    const session = sessionRows[0];
    if (session.expiresAt < Date.now()) {
      await db.delete(sessionsTable).where(eq(sessionsTable.token, token));
      return null;
    }

    const userRows = await db.select().from(usersTable).where(eq(usersTable.id, session.userId));
    if (userRows.length === 0) return null;

    const u = userRows[0];
    return {
      id: u.id,
      name: u.name,
      email: u.email,
      avatar: u.avatar,
      role: u.role as 'admin' | 'user',
      createdAt: u.createdAt,
      updatedAt: u.updatedAt
    };
  }

  async logout(token: string): Promise<void> {
    if (!token) return;
    await db.delete(sessionsTable).where(eq(sessionsTable.token, token));
  }

  async updateProfile(userId: string, data: { name?: string; avatar?: string; password?: string }): Promise<User> {
    const rows = await db.select().from(usersTable).where(eq(usersTable.id, userId));
    if (rows.length === 0) {
      throw new Error('User not found.');
    }

    const updates: any = { updatedAt: Date.now() };
    if (data.name && data.name.trim().length >= 2) {
      updates.name = data.name.trim();
    }
    if (data.avatar) {
      updates.avatar = data.avatar.trim();
    }
    if (data.password && data.password.length >= 6) {
      updates.passwordHash = this.hashPassword(data.password);
    }

    await db.update(usersTable).set(updates).where(eq(usersTable.id, userId));

    const updated = await db.select().from(usersTable).where(eq(usersTable.id, userId));
    const u = updated[0];
    return {
      id: u.id,
      name: u.name,
      email: u.email,
      avatar: u.avatar,
      role: u.role as 'admin' | 'user',
      createdAt: u.createdAt,
      updatedAt: u.updatedAt
    };
  }

  // --- Admin User Management Methods ---

  async getAllUsers(): Promise<User[]> {
    const rows = await db.select().from(usersTable);
    return rows.map((u) => ({
      id: u.id,
      name: u.name,
      email: u.email,
      avatar: u.avatar,
      role: u.role as 'admin' | 'user',
      createdAt: u.createdAt,
      updatedAt: u.updatedAt
    }));
  }

  async createUserByAdmin(data: {
    name: string;
    email: string;
    password: string;
    role?: 'admin' | 'user';
    avatar?: string;
  }): Promise<User> {
    const cleanEmail = data.email.trim().toLowerCase();
    const cleanName = data.name.trim();

    if (!cleanEmail || !cleanEmail.includes('@')) {
      throw new Error('Valid email address is required.');
    }
    if (!cleanName || cleanName.length < 2) {
      throw new Error('Name must be at least 2 characters.');
    }
    if (!data.password || data.password.length < 6) {
      throw new Error('Password must be at least 6 characters.');
    }

    const existing = await db.select().from(usersTable).where(eq(usersTable.email, cleanEmail));
    if (existing.length > 0) {
      throw new Error('A user with this email address already exists.');
    }

    const userId = `usr_${Date.now()}_${crypto.randomBytes(4).toString('hex')}`;
    const passwordHash = this.hashPassword(data.password);
    const avatar = data.avatar?.trim() || cleanName.charAt(0).toUpperCase();
    const role = data.role === 'admin' ? 'admin' : 'user';

    const newUser = {
      id: userId,
      name: cleanName,
      email: cleanEmail,
      passwordHash,
      avatar,
      role,
      createdAt: Date.now(),
      updatedAt: Date.now()
    };

    await db.insert(usersTable).values(newUser);

    return {
      id: newUser.id,
      name: newUser.name,
      email: newUser.email,
      avatar: newUser.avatar,
      role: newUser.role as 'admin' | 'user',
      createdAt: newUser.createdAt,
      updatedAt: newUser.updatedAt
    };
  }

  async updateUserByAdmin(
    userId: string,
    data: { name?: string; email?: string; role?: 'admin' | 'user'; avatar?: string }
  ): Promise<User> {
    const existing = await db.select().from(usersTable).where(eq(usersTable.id, userId));
    if (existing.length === 0) {
      throw new Error('User not found.');
    }

    const updates: any = { updatedAt: Date.now() };
    if (data.name && data.name.trim().length >= 2) {
      updates.name = data.name.trim();
    }
    if (data.email && data.email.includes('@')) {
      updates.email = data.email.trim().toLowerCase();
    }
    if (data.role && (data.role === 'admin' || data.role === 'user')) {
      updates.role = data.role;
    }
    if (data.avatar) {
      updates.avatar = data.avatar.trim();
    }

    await db.update(usersTable).set(updates).where(eq(usersTable.id, userId));

    const updated = await db.select().from(usersTable).where(eq(usersTable.id, userId));
    const u = updated[0];
    return {
      id: u.id,
      name: u.name,
      email: u.email,
      avatar: u.avatar,
      role: u.role as 'admin' | 'user',
      createdAt: u.createdAt,
      updatedAt: u.updatedAt
    };
  }

  async resetUserPasswordByAdmin(userId: string, newPassword: string): Promise<void> {
    if (!newPassword || newPassword.length < 6) {
      throw new Error('New password must be at least 6 characters.');
    }
    const existing = await db.select().from(usersTable).where(eq(usersTable.id, userId));
    if (existing.length === 0) {
      throw new Error('User not found.');
    }
    const passwordHash = this.hashPassword(newPassword);
    await db.update(usersTable).set({ passwordHash, updatedAt: Date.now() }).where(eq(usersTable.id, userId));
  }

  async deleteUserByAdmin(userId: string): Promise<void> {
    const existing = await db.select().from(usersTable).where(eq(usersTable.id, userId));
    if (existing.length === 0) {
      throw new Error('User not found.');
    }
    if (existing[0].email === 'admin@ruxt.org') {
      throw new Error('The primary root admin account cannot be deleted.');
    }

    // Delete user sessions
    await db.delete(sessionsTable).where(eq(sessionsTable.userId, userId));
    // Delete user
    await db.delete(usersTable).where(eq(usersTable.id, userId));
  }
}

export const authService = new AuthService();
