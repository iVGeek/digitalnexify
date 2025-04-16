import { Request, Response } from 'express';
import SubscriptionService from '../services/subscriptionService';

class SubscriptionController {
    private subscriptionService: SubscriptionService;

    constructor() {
        this.subscriptionService = new SubscriptionService();
    }

    public createSubscription = async (req: Request, res: Response): Promise<void> => {
        try {
            const subscriptionData = req.body;
            const newSubscription = await this.subscriptionService.createSubscription(subscriptionData);
            res.status(201).json(newSubscription);
        } catch (error) {
            res.status(500).json({ message: 'Error creating subscription', error });
        }
    };

    public getSubscriptionDetails = async (req: Request, res: Response): Promise<void> => {
        try {
            const { id } = req.params;
            const subscription = await this.subscriptionService.getSubscriptionDetails(id);
            if (subscription) {
                res.status(200).json(subscription);
            } else {
                res.status(404).json({ message: 'Subscription not found' });
            }
        } catch (error) {
            res.status(500).json({ message: 'Error fetching subscription details', error });
        }
    };

    public updateSubscription = async (req: Request, res: Response): Promise<void> => {
        try {
            const { id } = req.params;
            const updatedData = req.body;
            const updatedSubscription = await this.subscriptionService.updateSubscription(id, updatedData);
            if (updatedSubscription) {
                res.status(200).json(updatedSubscription);
            } else {
                res.status(404).json({ message: 'Subscription not found' });
            }
        } catch (error) {
            res.status(500).json({ message: 'Error updating subscription', error });
        }
    };
}

export default SubscriptionController;