import { User } from '../models/userModel';

export class UserService {
    async createUser(userData: { username: string; email: string; password: string; }) {
        const user = new User(userData);
        return await user.save();
    }

    async getUserById(userId: string) {
        return await User.findById(userId);
    }

    async validateUserCredentials(email: string, password: string) {
        const user = await User.findOne({ email });
        if (user && user.password === password) {
            return user;
        }
        return null;
    }

    async updateUser(userId: string, updateData: Partial<{ username: string; email: string; password: string; }>) {
        return await User.findByIdAndUpdate(userId, updateData, { new: true });
    }

    async deleteUser(userId: string) {
        return await User.findByIdAndDelete(userId);
    }
}