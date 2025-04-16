import { Subscription } from '../models/subscriptionModel';
import { User } from '../models/userModel';

export class SubscriptionService {
    async createSubscription(userId: string, planType: string): Promise<Subscription> {
        const newSubscription = new Subscription({
            userId,
            planType,
            startDate: new Date(),
            endDate: this.calculateEndDate(planType),
        });
        return await newSubscription.save();
    }

    async getSubscriptionDetails(userId: string): Promise<Subscription | null> {
        return await Subscription.findOne({ userId });
    }

    async updateSubscription(userId: string, planType: string): Promise<Subscription | null> {
        return await Subscription.findOneAndUpdate(
            { userId },
            { planType, endDate: this.calculateEndDate(planType) },
            { new: true }
        );
    }

    async checkSubscriptionStatus(userId: string): Promise<string> {
        const subscription = await this.getSubscriptionDetails(userId);
        if (!subscription) {
            return 'No active subscription';
        }
        const currentDate = new Date();
        return currentDate > subscription.endDate ? 'Subscription expired' : 'Active subscription';
    }

    private calculateEndDate(planType: string): Date {
        const duration = planType === 'monthly' ? 30 : 365; // Assuming monthly or yearly plans
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + duration);
        return endDate;
    }
}