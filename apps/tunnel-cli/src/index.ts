import WebSocket from 'ws';
import { exec, execFileSync } from 'child_process';
import fs from 'fs';

console.log(`\n======================================================`);
console.log(`⚡ RuXt Agent Tunnel (Local Outbound Companion)`);
console.log(`======================================================\n`);

function detectLocalTools() {
  const tools: Record<string, boolean> = {
    antigravity: fs.existsSync('C:\\Users\\isank\\AppData\\Local\\Programs\\Antigravity IDE\\bin\\antigravity-ide.cmd') ||
                 fs.existsSync('C:\\Users\\isank\\AppData\\Local\\Programs\\Antigravity IDE\\bin\\antigravity-ide'),
    cursor: false,
    claude: false,
    ollama: false
  };

  try {
    const bin = process.platform === 'win32' ? 'where' : 'which';
    execFileSync(bin, ['cursor'], { stdio: 'ignore' });
    tools.cursor = true;
  } catch (_) {}

  try {
    const bin = process.platform === 'win32' ? 'where' : 'which';
    execFileSync(bin, ['claude'], { stdio: 'ignore' });
    tools.claude = true;
  } catch (_) {}

  try {
    const bin = process.platform === 'win32' ? 'where' : 'which';
    execFileSync(bin, ['ollama'], { stdio: 'ignore' });
    tools.ollama = true;
  } catch (_) {}

  return tools;
}

const tools = detectLocalTools();
console.log(`Local Environment Detection:`);
console.log(`- Antigravity IDE: ${tools.antigravity ? '✅ Connected' : '❌ Standby'}`);
console.log(`- Cursor:          ${tools.cursor ? '✅ Ready' : '⚪ Not in PATH'}`);
console.log(`- Claude Code:     ${tools.claude ? '✅ Ready' : '⚪ Not in PATH'}`);
console.log(`- Ollama CLI:      ${tools.ollama ? '✅ Detected' : '⚪ Not in PATH'}`);

const targetServer = process.env.RUXT_HOST || 'ws://localhost:1234/ws';
console.log(`\nInitiating Outbound WebSocket to ${targetServer}...`);

function connect() {
  const ws = new WebSocket(targetServer);

  ws.on('open', () => {
    console.log(`✅ Secure Tunnel Established with RuXt Host!`);
    ws.send(JSON.stringify({
      type: 'tunnel:register',
      payload: {
        platform: process.platform,
        tools,
        pid: process.pid,
        cwd: process.cwd()
      }
    }));
  });

  ws.on('message', (data) => {
    try {
      const msg = JSON.parse(data.toString());
      console.log(`[Tunnel Event Received]:`, msg.type || msg.event);

      // Handle remote command execution request from RuXt Host
      if (msg.type === 'tunnel:exec_command') {
        const { id, command } = msg.payload || {};
        console.log(`[Tunnel Executing Command]: "${command}"`);

        exec(command, { maxBuffer: 1024 * 1024 * 4 }, (error, stdout, stderr) => {
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
  });

  ws.on('close', () => {
    console.log(`Tunnel disconnected. Retrying connection in 4s...`);
    setTimeout(connect, 4000);
  });

  ws.on('error', (err: any) => {
    console.log(`Tunnel standby (waiting for host at ${targetServer}). Error: ${err.message || 'offline'}`);
  });
}

connect();
