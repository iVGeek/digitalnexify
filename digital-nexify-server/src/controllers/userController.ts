import { Request, Response } from 'express';
import UserService from '../services/userService';

class UserController {
    private userService: UserService;

    constructor() {
        this.userService = new UserService();
    }

    public registerUser = async (req: Request, res: Response): Promise<void> => {
        try {
            const userData = req.body;
            const newUser = await this.userService.createUser(userData);
            res.status(201).json(newUser);
        } catch (error) {
            res.status(500).json({ message: 'Error registering user', error });
        }
    };

    public loginUser = async (req: Request, res: Response): Promise<void> => {
        try {
            const { email, password } = req.body;
            const user = await this.userService.validateUser(email, password);
            if (user) {
                res.status(200).json(user);
            } else {
                res.status(401).json({ message: 'Invalid credentials' });
            }
        } catch (error) {
            res.status(500).json({ message: 'Error logging in', error });
        }
    };

    public getUserDetails = async (req: Request, res: Response): Promise<void> => {
        try {
            const userId = req.params.id;
            const userDetails = await this.userService.getUserById(userId);
            if (userDetails) {
                res.status(200).json(userDetails);
            } else {
                res.status(404).json({ message: 'User not found' });
            }
        } catch (error) {
            res.status(500).json({ message: 'Error fetching user details', error });
        }
    };
}

export default UserController;