-- Coordenadas geográficas para eventos (mapas Leaflet)
ALTER TABLE events
    ADD COLUMN latitude  DECIMAL(10, 8) DEFAULT NULL,
    ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL;
