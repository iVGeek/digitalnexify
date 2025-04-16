import { Router } from 'express';
import SubscriptionController from '../controllers/subscriptionController';

const router = Router();
const subscriptionController = new SubscriptionController();

const setSubscriptionRoutes = (app) => {
    app.use('/api/subscriptions', router);

    router.post('/', subscriptionController.createSubscription);
    router.get('/:userId', subscriptionController.getSubscriptionDetails);
    router.put('/:subscriptionId', subscriptionController.updateSubscription);
};

export default setSubscriptionRoutes;