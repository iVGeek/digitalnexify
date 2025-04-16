import { Sequelize } from 'sequelize';
import { config } from './environment';

const sequelize = new Sequelize(config.database.name, config.database.user, config.database.password, {
    host: config.database.host,
    dialect: 'postgres', // or 'mysql', 'sqlite', etc.
    logging: false, // Set to true for SQL query logging
});

export default sequelize;