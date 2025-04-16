import { Router } from 'express';
import UserController from '../controllers/userController';

const router = Router();
const userController = new UserController();

export const setUserRoutes = (app) => {
    app.post('/api/users/register', userController.registerUser);
    app.post('/api/users/login', userController.loginUser);
    app.get('/api/users/:id', userController.getUserDetails);
};