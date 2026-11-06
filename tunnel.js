#!/usr/bin/env node
/**
 * ⚡ RuXt Standalone Tunnel Companion (Zero-Dependency)
 * Can be run in ANY project directory (HTML, PHP, Python, React, etc.)
 * Requires ONLY Node.js (v18+) — NO `npm install` needed!
 */

import { exec, execFileSync } from 'child_process';
import fs from 'fs';
import path from 'path';

// Use native WebSocket (built into Node 18+) or fallback to 'ws' if available
const WS = typeof WebSocket !== 'undefined'
  ? WebSocket
  : await (async () => {
      try {
        const mod = await import('ws');
        return mod.default || mod;
      } catch (_) {
        console.error('❌ Error: Node.js 18+ with native WebSocket or "ws" module is required.');
        process.exit(1);
      }
    })();

console.log(`\n======================================================`);
console.log(`⚡ RuXt Agent Tunnel (Zero-Dependency Standalone)`);
console.log(`======================================================\n`);

// Parse arguments: e.g. `node tunnel.js https://app.ruxt.org --dir "C:/my-html-project"`
const args = process.argv.slice(2);
let targetUrl = 'https://app.ruxt.org';
let targetDir = process.cwd();

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--dir' && args[i + 1]) {
    targetDir = path.resolve(args[i + 1]);
    i++;
  } else if (!args[i].startsWith('--')) {
    targetUrl = args[i];
  }
}

// Normalize WebSocket URL
let rawTarget = targetUrl;
if (!rawTarget.startsWith('ws://') && !rawTarget.startsWith('wss://')) {
  if (rawTarget.startsWith('https://')) {
    rawTarget = rawTarget.replace('https://', 'wss://');
  } else if (rawTarget.startsWith('http://')) {
    rawTarget = rawTarget.replace('http://', 'ws://');
  } else {
    rawTarget = (rawTarget.includes('localhost') ? 'ws://' : 'wss://') + rawTarget;
  }
}
if (!rawTarget.endsWith('/ws')) {
  rawTarget = rawTarget.replace(/\/+$/, '') + '/ws';
}

function detectTools() {
  const tools = {
    antigravity: false,
    cursor: false,
    claude: false,
    ollama: false,
    git: false,
    node: true
  };

  const bin = process.platform === 'win32' ? 'where' : 'which';
  const check = (cmd) => {
    try {
      execFileSync(bin, [cmd], { stdio: 'ignore' });
      return true;
    } catch (_) {
      return false;
    }
  };

  tools.antigravity = check('antigravity-ide') || check('agy') || check('antigravity');
  if (!tools.antigravity && process.platform === 'win32') {
    const local = process.env.LOCALAPPDATA || '';
    tools.antigravity = Boolean(local) && fs.existsSync(path.join(local, 'Programs', 'Antigravity IDE', 'antigravity.exe'));
  }
  tools.cursor = check('cursor');
  tools.claude = check('claude');
  tools.ollama = check('ollama');
  tools.git = check('git');

  return tools;
}

const detected = detectTools();
console.log(`Working Directory: ${targetDir}`);
console.log(`Local Environment:`);
console.log(`- Antigravity IDE: ${detected.antigravity ? '✅ Connected' : '⚪ Standby'}`);
console.log(`- Cursor IDE:      ${detected.cursor ? '✅ Ready' : '⚪ Not in PATH'}`);
console.log(`- Claude Code:     ${detected.claude ? '✅ Ready' : '⚪ Not in PATH'}`);
console.log(`- Git:             ${detected.git ? '✅ Available' : '⚪ Not in PATH'}`);
console.log(`\nConnecting outbound WebSocket to ${rawTarget}...\n`);

function connect() {
  const ws = new WS(rawTarget);

  ws.onopen = () => {
    console.log(`✅ Secure Tunnel Established with RuXt Host!`);
    console.log(`🎯 Connected project directory: ${targetDir}`);
    console.log(`⚡ RuXt Agents can now collaborate on files and terminal commands in this folder.\n`);

    ws.send(JSON.stringify({
      type: 'tunnel:register',
      payload: {
        platform: process.platform,
        tools: detected,
        pid: process.pid,
        cwd: targetDir
      }
    }));
  };

  ws.onmessage = (event) => {
    try {
      const msg = JSON.parse(event.data.toString());
      if (msg.type === 'tunnel:exec_command') {
        const { id, command } = msg.payload || {};
        console.log(`[Executing in ${targetDir}]: "${command}"`);

        exec(command, { cwd: targetDir, maxBuffer: 1024 * 1024 * 4 }, (error, stdout, stderr) => {
          ws.send(JSON.stringify({
            type: 'tunnel:exec_result',
            payload: {
              id,
              success: !error,
              exitCode: error ? error.code || 1 : 0,
              stdout: stdout.toString(),
              stderr: stderr.toString()
            }
          }));
        });
      }
    } catch (_) {}
  };

  ws.onclose = () => {
    console.log(`Tunnel disconnected. Retrying in 4s...`);
    setTimeout(connect, 4000);
  };

  ws.onerror = (err) => {
    console.log(`Tunnel waiting for host at ${rawTarget}... (${err.message || 'connecting'})`);
  };
}

connect();
