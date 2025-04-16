import { config } from 'dotenv';

config();

export const environment = {
    PORT: process.env.PORT || 3000,
    DB_URI: process.env.DB_URI || 'mongodb://localhost:27017/digitalnexify',
    JWT_SECRET: process.env.JWT_SECRET || 'your_jwt_secret',
    API_KEY: process.env.API_KEY || 'your_api_key',
    NODE_ENV: process.env.NODE_ENV || 'development',
};