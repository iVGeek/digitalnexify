import { Schema, model } from 'mongoose';

const subscriptionSchema = new Schema({
    userId: {
        type: Schema.Types.ObjectId,
        required: true,
        ref: 'User'
    },
    planType: {
        type: String,
        required: true,
        enum: ['Basic', 'Standard', 'Premium']
    },
    startDate: {
        type: Date,
        required: true,
        default: Date.now
    },
    endDate: {
        type: Date,
        required: true
    }
}, {
    timestamps: true
});

const Subscription = model('Subscription', subscriptionSchema);

export default Subscription;