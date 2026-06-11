module.exports = {
  apps: [
    {
      name: 'backend-api-masjidkassiti',
      script: 'artisan',
      args: 'octane:start --server=frankenphp --host=127.0.0.1 --port=8000',
      interpreter: 'php',
      instances: 1, // Octane handles its own workers via FrankenPHP
      exec_mode: 'fork',
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        APP_ENV: 'production',
        OCTANE_SERVER: 'frankenphp',
      },
    },
  ],
};
