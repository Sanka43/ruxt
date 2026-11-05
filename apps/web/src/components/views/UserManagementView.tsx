import React, { useState, useEffect } from 'react';
import { User } from '../../types';
import {
  getStoredUsers,
  upsertStoredUser,
  updateStoredUser,
  updateStoredUserPassword,
  deleteStoredUser
} from '../../utils/userRegistry';
import {
  Users,
  UserPlus,
  Shield,
  ShieldCheck,
  Search,
  KeyRound,
  Trash2,
  Edit2,
  X,
  Check,
  AlertCircle,
  Mail,
  Calendar,
  Lock,
  ArrowUpDown
} from 'lucide-react';

interface UserManagementViewProps {
  currentUser: User | null;
}

export const UserManagementView: React.FC<UserManagementViewProps> = ({ currentUser }) => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [search, setSearch] = useState<string>('');
  const [roleFilter, setRoleFilter] = useState<'all' | 'admin' | 'user'>('all');
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Modals state
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);
  const [resettingUser, setResettingUser] = useState<User | null>(null);

  // Form states
  const [formName, setFormName] = useState('');
  const [formEmail, setFormEmail] = useState('');
  const [formPassword, setFormPassword] = useState('');
  const [formRole, setFormRole] = useState<'admin' | 'user'>('user');
  const [newPassword, setNewPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    setToast({ type, message });
    setTimeout(() => setToast(null), 3500);
  };

  const getAuthToken = () => localStorage.getItem('ruxt_auth_token') || '';

  const fetchUsers = async () => {
    setLoading(true);
    const token = getAuthToken();

    // 1. Immediately load local registry users (guarantees registered users show up)
    const stored = getStoredUsers();
    setUsers(stored);

    // 2. Query backend SQLite database if online
    try {
      const res = await fetch('/api/admin/users', {
        headers: { Authorization: `Bearer ${token}` }
      });

      const contentType = res.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const data = await res.json();
        if (data.ok && Array.isArray(data.users)) {
          // Merge server users with local stored users
          const mergedMap = new Map<string, User>();
          stored.forEach((u) => mergedMap.set(u.email.toLowerCase(), u));
          data.users.forEach((u: User) => {
            const existing = mergedMap.get(u.email.toLowerCase());
            mergedMap.set(u.email.toLowerCase(), {
              ...(existing || {}),
              ...u
            });
          });
          const mergedList = Array.from(mergedMap.values());
          setUsers(mergedList);
          mergedList.forEach((u) => upsertStoredUser(u as any));
        }
      }
    } catch (_) {
      // In static / offline mode, local registry is already active
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  // Filter users
  const filteredUsers = users.filter((u) => {
    const matchesSearch =
      u.name.toLowerCase().includes(search.toLowerCase()) ||
      u.email.toLowerCase().includes(search.toLowerCase());
    const matchesRole = roleFilter === 'all' || u.role === roleFilter;
    return matchesSearch && matchesRole;
  });

  const totalAdmins = users.filter((u) => u.role === 'admin').length;
  const totalRegular = users.filter((u) => u.role === 'user').length;

  // Create user handler
  const handleCreateUser = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formName.trim() || !formEmail.trim() || formPassword.length < 6) {
      showToast('Name, valid email and password (min 6 chars) are required.', 'error');
      return;
    }

    setSubmitting(true);
    const token = getAuthToken();

    // Save directly to local persistent registry
    const newUser = upsertStoredUser({
      name: formName.trim(),
      email: formEmail.trim().toLowerCase(),
      password: formPassword,
      role: formRole,
      avatar: formName.trim().charAt(0).toUpperCase()
    });

    // Also attempt server sync if running
    try {
      await fetch('/api/admin/users', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({
          name: formName.trim(),
          email: formEmail.trim(),
          password: formPassword,
          role: formRole
        })
      });
    } catch (_) {}

    showToast(`User ${newUser.name} created successfully.`);
    setIsAddOpen(false);
    resetForm();
    fetchUsers();
    setSubmitting(false);
  };

  // Update user handler
  const handleUpdateUser = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingUser) return;

    setSubmitting(true);
    const token = getAuthToken();

    // Update in local registry
    updateStoredUser(editingUser.id, {
      name: formName.trim(),
      email: formEmail.trim().toLowerCase(),
      role: formRole
    });

    // Also attempt server sync if running
    try {
      await fetch(`/api/admin/users/${editingUser.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({
          name: formName.trim(),
          email: formEmail.trim(),
          role: formRole
        })
      });
    } catch (_) {}

    showToast(`User ${formName.trim()} updated.`);
    setEditingUser(null);
    resetForm();
    fetchUsers();
    setSubmitting(false);
  };

  // Reset password handler
  const handleResetPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!resettingUser || newPassword.length < 6) {
      showToast('Password must be at least 6 characters.', 'error');
      return;
    }

    setSubmitting(true);
    const token = getAuthToken();

    // Update in local registry
    updateStoredUserPassword(resettingUser.id, newPassword);

    // Also attempt server sync if running
    try {
      await fetch(`/api/admin/users/${resettingUser.id}/password`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({ password: newPassword })
      });
    } catch (_) {}

    showToast(`Password updated for ${resettingUser.name}.`);
    setResettingUser(null);
    setNewPassword('');
    setSubmitting(false);
  };

  // Delete user handler
  const handleDeleteUser = async (user: User) => {
    if (user.email === 'admin@ruxt.org') {
      showToast('The root admin account cannot be deleted.', 'error');
      return;
    }

    if (!window.confirm(`Are you sure you want to delete user "${user.name}" (${user.email})?`)) {
      return;
    }

    const token = getAuthToken();

    // Delete from local registry
    deleteStoredUser(user.id);

    // Also attempt server sync if running
    try {
      await fetch(`/api/admin/users/${user.id}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${token}` }
      });
    } catch (_) {}

    showToast(`User ${user.name} removed.`);
    fetchUsers();
  };

  const resetForm = () => {
    setFormName('');
    setFormEmail('');
    setFormPassword('');
    setFormRole('user');
    setNewPassword('');
  };

  const openEditModal = (user: User) => {
    setEditingUser(user);
    setFormName(user.name);
    setFormEmail(user.email);
    setFormRole(user.role);
  };

  return (
    <div className="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 max-w-6xl mx-auto w-full select-none transition-colors">
      {/* Toast Alert */}
      {toast && (
        <div
          className={`fixed top-5 right-5 z-50 p-3.5 rounded-xl border shadow-lg flex items-center space-x-2 text-xs animate-fade-in ${
            toast.type === 'success' ? 'text-emerald-500 bg-emerald-500/10 border-emerald-500/30' : 'text-rose-500 bg-rose-500/10 border-rose-500/30'
          }`}
          style={{ backdropFilter: 'blur(8px)' }}
        >
          {toast.type === 'success' ? <Check className="w-4 h-4" /> : <AlertCircle className="w-4 h-4" />}
          <span className="font-medium">{toast.message}</span>
        </div>
      )}

      {/* Header Bar */}
      <div
        className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-4 border-b"
        style={{ borderColor: 'var(--line)' }}
      >
        <div>
          <div className="flex items-center space-x-2">
            <span className="eyebrow">ADMINISTRATOR CONTROL</span>
            <span className="text-[10px] uppercase font-mono font-bold px-2 py-0.5 rounded-full bg-indigo-500/15 text-indigo-500 border border-indigo-500/30">
              Admin Only
            </span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-bold tracking-tight mt-1" style={{ color: 'var(--ink)' }}>
            User Management
          </h1>
          <p className="text-xs sm:text-sm mt-0.5" style={{ color: 'var(--muted)' }}>
            Manage registered accounts, roles, credentials, and access permissions.
          </p>
        </div>

        <button
          onClick={() => {
            resetForm();
            setIsAddOpen(true);
          }}
          className="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-semibold shadow-xs transition cursor-pointer self-start sm:self-auto hover:opacity-90"
          style={{
            backgroundColor: 'var(--ruxt)',
            color: '#fff'
          }}
        >
          <UserPlus className="w-3.5 h-3.5" />
          <span>Add New User</span>
        </button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div
          className="p-4 rounded-xl border flex items-center space-x-3.5 shadow-xs transition"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <div
            className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
            style={{ backgroundColor: 'var(--accent-soft)', borderColor: 'var(--line)', color: 'var(--accent)' }}
          >
            <Users className="w-5 h-5" />
          </div>
          <div>
            <div className="text-[11px] font-medium uppercase" style={{ color: 'var(--muted)' }}>Total Users</div>
            <div className="text-xl font-bold tracking-tight" style={{ color: 'var(--ink)' }}>{users.length}</div>
          </div>
        </div>

        <div
          className="p-4 rounded-xl border flex items-center space-x-3.5 shadow-xs transition"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <div className="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-500 flex items-center justify-center shrink-0">
            <ShieldCheck className="w-5 h-5" />
          </div>
          <div>
            <div className="text-[11px] font-medium uppercase" style={{ color: 'var(--muted)' }}>Administrators</div>
            <div className="text-xl font-bold tracking-tight" style={{ color: 'var(--ink)' }}>{totalAdmins}</div>
          </div>
        </div>

        <div
          className="p-4 rounded-xl border flex items-center space-x-3.5 shadow-xs transition"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <div className="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 flex items-center justify-center shrink-0">
            <Users className="w-5 h-5" />
          </div>
          <div>
            <div className="text-[11px] font-medium uppercase" style={{ color: 'var(--muted)' }}>Standard Users</div>
            <div className="text-xl font-bold tracking-tight" style={{ color: 'var(--ink)' }}>{totalRegular}</div>
          </div>
        </div>
      </div>

      {/* Filters & Search Bar */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
        {/* Search */}
        <div
          className="w-full sm:w-80 flex items-center space-x-2 px-3 py-2 rounded-xl border shadow-xs transition focus-within:ring-2"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <Search className="w-4 h-4 text-slate-400 shrink-0" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search users by name or email..."
            className="flex-1 bg-transparent text-xs outline-none"
            style={{ color: 'var(--ink)' }}
          />
        </div>

        {/* Role Filter Tabs */}
        <div
          className="flex p-1 rounded-xl border text-xs font-semibold self-stretch sm:self-auto"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          {(['all', 'admin', 'user'] as const).map((r) => (
            <button
              key={r}
              onClick={() => setRoleFilter(r)}
              className="px-3 py-1 rounded-lg capitalize transition cursor-pointer"
              style={
                roleFilter === r
                  ? { backgroundColor: 'var(--accent)', color: '#fff' }
                  : { color: 'var(--muted)' }
              }
            >
              {r === 'all' ? 'All Roles' : r}
            </button>
          ))}
        </div>
      </div>

      {/* Users Table */}
      <div
        className="rounded-2xl border overflow-hidden shadow-xs transition-colors"
        style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
      >
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr
                className="border-b text-[11px] uppercase tracking-wider font-semibold"
                style={{
                  backgroundColor: 'color-mix(in srgb, var(--surface) 90%, var(--bg))',
                  borderColor: 'var(--line)',
                  color: 'var(--muted)'
                }}
              >
                <th className="py-3 px-4">User</th>
                <th className="py-3 px-4">Role</th>
                <th className="py-3 px-4">Email</th>
                <th className="py-3 px-4">Joined Date</th>
                <th className="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y" style={{ borderColor: 'var(--line)' }}>
              {filteredUsers.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-xs" style={{ color: 'var(--muted)' }}>
                    No users found matching your filters.
                  </td>
                </tr>
              ) : (
                filteredUsers.map((u) => {
                  const isRootAdmin = u.email === 'admin@ruxt.org';
                  const isCurrent = currentUser?.id === u.id;

                  return (
                    <tr
                      key={u.id}
                      className="hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
                    >
                      {/* Name & Avatar */}
                      <td className="py-3.5 px-4">
                        <div className="flex items-center space-x-3">
                          <div
                            className="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 shadow-xs border"
                            style={{
                              backgroundColor: 'var(--accent-soft)',
                              borderColor: 'var(--line)',
                              color: 'var(--accent)'
                            }}
                          >
                            {u.avatar || u.name.charAt(0).toUpperCase()}
                          </div>
                          <div>
                            <div className="font-semibold flex items-center space-x-1.5" style={{ color: 'var(--ink)' }}>
                              <span>{u.name}</span>
                              {isCurrent && (
                                <span className="text-[10px] font-mono opacity-60">(You)</span>
                              )}
                            </div>
                            <div className="text-[10px] font-mono" style={{ color: 'var(--muted)' }}>
                              {u.id}
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* Role Badge */}
                      <td className="py-3.5 px-4">
                        <span
                          className={`inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono border ${
                            u.role === 'admin'
                              ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
                              : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
                          }`}
                        >
                          {u.role === 'admin' ? <ShieldCheck className="w-3 h-3" /> : <Shield className="w-3 h-3" />}
                          <span>{u.role}</span>
                        </span>
                      </td>

                      {/* Email */}
                      <td className="py-3.5 px-4 font-mono text-xs" style={{ color: 'var(--muted)' }}>
                        {u.email}
                      </td>

                      {/* Joined Date */}
                      <td className="py-3.5 px-4" style={{ color: 'var(--muted)' }}>
                        {new Date(u.createdAt).toLocaleDateString([], {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric'
                        })}
                      </td>

                      {/* Actions */}
                      <td className="py-3.5 px-4 text-right">
                        <div className="inline-flex items-center space-x-1">
                          {/* Edit Details */}
                          <button
                            onClick={() => openEditModal(u)}
                            className="p-1.5 rounded-lg border hover:opacity-80 transition cursor-pointer"
                            style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)' }}
                            title="Edit User"
                          >
                            <Edit2 className="w-3.5 h-3.5" style={{ color: 'var(--ink)' }} />
                          </button>

                          {/* Reset Password */}
                          <button
                            onClick={() => {
                              setResettingUser(u);
                              setNewPassword('');
                            }}
                            className="p-1.5 rounded-lg border hover:opacity-80 transition cursor-pointer"
                            style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)' }}
                            title="Reset Password"
                          >
                            <KeyRound className="w-3.5 h-3.5 text-amber-500" />
                          </button>

                          {/* Delete (Protected for root admin) */}
                          {!isRootAdmin && (
                            <button
                              onClick={() => handleDeleteUser(u)}
                              className="p-1.5 rounded-lg border hover:opacity-80 transition cursor-pointer text-rose-500"
                              style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)' }}
                              title="Delete User"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* --- ADD USER MODAL --- */}
      {isAddOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
          <div
            className="w-full max-w-md rounded-2xl border shadow-2xl p-6 space-y-5"
            style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)', color: 'var(--ink)' }}
          >
            <div className="flex items-center justify-between pb-3 border-b" style={{ borderColor: 'var(--line)' }}>
              <div className="flex items-center space-x-2">
                <UserPlus className="w-4 h-4" style={{ color: 'var(--accent)' }} />
                <h3 className="font-bold text-sm">Add New User</h3>
              </div>
              <button
                onClick={() => setIsAddOpen(false)}
                className="p-1 rounded-lg hover:opacity-80 transition cursor-pointer"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleCreateUser} className="space-y-4 text-xs">
              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>Full Name</label>
                <input
                  type="text"
                  required
                  value={formName}
                  onChange={(e) => setFormName(e.target.value)}
                  placeholder="e.g. Kasun Silva"
                  className="w-full px-3 py-2 rounded-xl border outline-none"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                />
              </div>

              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>Email Address</label>
                <input
                  type="email"
                  required
                  value={formEmail}
                  onChange={(e) => setFormEmail(e.target.value)}
                  placeholder="kasun@company.com"
                  className="w-full px-3 py-2 rounded-xl border outline-none"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                />
              </div>

              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>Initial Password</label>
                <input
                  type="password"
                  required
                  value={formPassword}
                  onChange={(e) => setFormPassword(e.target.value)}
                  placeholder="Min 6 characters"
                  className="w-full px-3 py-2 rounded-xl border outline-none"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                />
              </div>

              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>Assigned Role</label>
                <select
                  value={formRole}
                  onChange={(e) => setFormRole(e.target.value as 'admin' | 'user')}
                  className="w-full px-3 py-2 rounded-xl border outline-none"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                >
                  <option value="user">Standard User</option>
                  <option value="admin">Workspace Administrator</option>
                </select>
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsAddOpen(false)}
                  className="px-3.5 py-2 rounded-xl border font-semibold hover:opacity-85 transition cursor-pointer"
                  style={{ borderColor: 'var(--line)' }}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="px-4 py-2 rounded-xl font-bold transition shadow-xs cursor-pointer disabled:opacity-50"
                  style={{ backgroundColor: 'var(--ruxt)', color: '#fff' }}
                >
                  {submitting ? 'Creating...' : 'Create Account'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* --- EDIT USER MODAL --- */}
      {editingUser && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
          <div
            className="w-full max-w-md rounded-2xl border shadow-2xl p-6 space-y-5"
            style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)', color: 'var(--ink)' }}
          >
            <div className="flex items-center justify-between pb-3 border-b" style={{ borderColor: 'var(--line)' }}>
              <div className="flex items-center space-x-2">
                <Edit2 className="w-4 h-4" style={{ color: 'var(--accent)' }} />
                <h3 className="font-bold text-sm">Edit User: {editingUser.name}</h3>
              </div>
              <button
                onClick={() => setEditingUser(null)}
                className="p-1 rounded-lg hover:opacity-80 transition cursor-pointer"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleUpdateUser} className="space-y-4 text-xs">
              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>Full Name</label>
                <input
                  type="text"
                  required
                  value={formName}
                  onChange={(e) => setFormName(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border outline-none"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                />
              </div>

              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>Email Address</label>
                <input
                  type="email"
                  required
                  value={formEmail}
                  onChange={(e) => setFormEmail(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border outline-none"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                />
              </div>

              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>Assigned Role</label>
                <select
                  value={formRole}
                  onChange={(e) => setFormRole(e.target.value as 'admin' | 'user')}
                  disabled={editingUser.email === 'admin@ruxt.org'}
                  className="w-full px-3 py-2 rounded-xl border outline-none disabled:opacity-50"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                >
                  <option value="user">Standard User</option>
                  <option value="admin">Workspace Administrator</option>
                </select>
                {editingUser.email === 'admin@ruxt.org' && (
                  <p className="text-[10px] text-amber-500">The primary root admin must retain Administrator role.</p>
                )}
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setEditingUser(null)}
                  className="px-3.5 py-2 rounded-xl border font-semibold hover:opacity-85 transition cursor-pointer"
                  style={{ borderColor: 'var(--line)' }}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="px-4 py-2 rounded-xl font-bold transition shadow-xs cursor-pointer disabled:opacity-50"
                  style={{ backgroundColor: 'var(--ruxt)', color: '#fff' }}
                >
                  {submitting ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* --- RESET PASSWORD MODAL --- */}
      {resettingUser && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
          <div
            className="w-full max-w-sm rounded-2xl border shadow-2xl p-6 space-y-5"
            style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)', color: 'var(--ink)' }}
          >
            <div className="flex items-center justify-between pb-3 border-b" style={{ borderColor: 'var(--line)' }}>
              <div className="flex items-center space-x-2">
                <KeyRound className="w-4 h-4 text-amber-500" />
                <h3 className="font-bold text-sm">Reset Password</h3>
              </div>
              <button
                onClick={() => setResettingUser(null)}
                className="p-1 rounded-lg hover:opacity-80 transition cursor-pointer"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <p className="text-xs" style={{ color: 'var(--muted)' }}>
              Set a new secure password for <strong style={{ color: 'var(--ink)' }}>{resettingUser.name}</strong> ({resettingUser.email}).
            </p>

            <form onSubmit={handleResetPassword} className="space-y-4 text-xs">
              <div className="space-y-1">
                <label className="font-semibold" style={{ color: 'var(--muted)' }}>New Password</label>
                <input
                  type="password"
                  required
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  placeholder="Min 6 characters"
                  className="w-full px-3 py-2 rounded-xl border outline-none font-mono"
                  style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
                />
              </div>

              <div className="flex items-center justify-end space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setResettingUser(null)}
                  className="px-3.5 py-2 rounded-xl border font-semibold hover:opacity-85 transition cursor-pointer"
                  style={{ borderColor: 'var(--line)' }}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="px-4 py-2 rounded-xl font-bold transition shadow-xs cursor-pointer disabled:opacity-50"
                  style={{ backgroundColor: 'var(--accent)', color: '#fff' }}
                >
                  {submitting ? 'Resetting...' : 'Update Password'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
