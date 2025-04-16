import express from 'express';
import mongoose from 'mongoose';
import { setUserRoutes } from './routes/userRoutes';
import { setSubscriptionRoutes } from './routes/subscriptionRoutes';
import { config } from './config/environment';

const app = express();

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Database connection
mongoose.connect(config.database.connectionString, {
    useNewUrlParser: true,
    useUnifiedTopology: true,
})
.then(() => {
    console.log('Database connected successfully');
})
.catch((error) => {
    console.error('Database connection error:', error);
});

// Routes
setUserRoutes(app);
setSubscriptionRoutes(app);

// Start the server
const PORT = config.server.port || 3000;
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});