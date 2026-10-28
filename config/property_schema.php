<?php

declare(strict_types=1);

/**
 * Single source of truth for type-specific property fields.
 *
 * Global fields (price, area, location, purpose…) are real columns on
 * `properties`. Everything defined here is stored in the `attributes` JSONB
 * column, validated from this file, and rendered by the frontend form which
 * reads the same definition via GET /api/property-schema.
 *
 * `groups` are reusable field sets. `types` maps a property_type slug to the
 * groups it shows, so adding a type is an edit here rather than a migration.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Reusable field groups
    |--------------------------------------------------------------------------
    | field kinds: int | decimal | bool | text | select | multiselect
    */

    'groups' => [

        /*
         * Document section 6: availability shown to buyers, distinct from the
         * listing's own workflow status (draft / pending / published).
         * Applies to every type, so it carries 'always' => true.
         */
        'availability' => [
            'label' => 'Availability',
            'always' => true,
            'fields' => [
                [
                    'key' => 'propertyStatus', 'label' => 'Property Status', 'kind' => 'select',
                    'options' => [
                        ['value' => 'ready_to_move', 'label' => 'Ready to Move'],
                        ['value' => 'under_construction', 'label' => 'Under Construction'],
                        ['value' => 'booking_open', 'label' => 'Booking Open'],
                        ['value' => 'possession_available', 'label' => 'Possession Available'],
                        ['value' => 'possession_soon', 'label' => 'Possession Soon'],
                        ['value' => 'pre_launch', 'label' => 'Pre-Launch'],
                        ['value' => 'completed', 'label' => 'Completed'],
                    ],
                ],
            ],
        ],

        'rooms' => [
            'label' => 'Rooms',
            'fields' => [
                ['key' => 'attachedBathrooms', 'label' => 'Attached Bathrooms', 'kind' => 'int', 'max' => 99],
                ['key' => 'drawingRoom', 'label' => 'Drawing Room', 'kind' => 'int', 'max' => 99],
                ['key' => 'diningRoom', 'label' => 'Dining Room', 'kind' => 'int', 'max' => 99],
                ['key' => 'tvLounge', 'label' => 'TV Lounge', 'kind' => 'int', 'max' => 99],
                ['key' => 'studyRoom', 'label' => 'Study Room', 'kind' => 'int', 'max' => 99],
                ['key' => 'storeRoom', 'label' => 'Store Room', 'kind' => 'int', 'max' => 99],
                ['key' => 'servantQuarter', 'label' => 'Servant Quarter', 'kind' => 'int', 'max' => 99],
                ['key' => 'laundryArea', 'label' => 'Laundry Area', 'kind' => 'bool'],
            ],
        ],

        'structure' => [
            'label' => 'Structure',
            'fields' => [
                ['key' => 'coveredArea', 'label' => 'Covered Area (Sq. Ft.)', 'kind' => 'decimal'],
                ['key' => 'totalFloors', 'label' => 'Total Floors', 'kind' => 'int', 'max' => 200],
                ['key' => 'constructionYear', 'label' => 'Construction Year', 'kind' => 'int', 'min' => 1900, 'max' => 2100],
                ['key' => 'basement', 'label' => 'Basement', 'kind' => 'bool'],
                ['key' => 'terrace', 'label' => 'Terrace', 'kind' => 'bool'],
                ['key' => 'balcony', 'label' => 'Balcony', 'kind' => 'int', 'max' => 99],
                ['key' => 'garage', 'label' => 'Garage', 'kind' => 'bool'],
            ],
        ],

        'utilities' => [
            'label' => 'Utilities',
            'fields' => [
                ['key' => 'electricity', 'label' => 'Electricity', 'kind' => 'bool'],
                ['key' => 'gas', 'label' => 'Gas', 'kind' => 'bool'],
                ['key' => 'water', 'label' => 'Water', 'kind' => 'bool'],
                ['key' => 'sewerage', 'label' => 'Sewerage', 'kind' => 'bool'],
                ['key' => 'internet', 'label' => 'Internet', 'kind' => 'bool'],
                ['key' => 'cableTv', 'label' => 'Cable TV', 'kind' => 'bool'],
                ['key' => 'solarSystem', 'label' => 'Solar System', 'kind' => 'bool'],
            ],
        ],

        'comfort' => [
            'label' => 'Comfort & Safety',
            'fields' => [
                ['key' => 'centralHeating', 'label' => 'Central Heating', 'kind' => 'bool'],
                ['key' => 'airConditioning', 'label' => 'Air Conditioning', 'kind' => 'bool'],
                ['key' => 'doubleGlazedWindows', 'label' => 'Double Glazed Windows', 'kind' => 'bool'],
                ['key' => 'security', 'label' => 'Security', 'kind' => 'bool'],
                ['key' => 'cctv', 'label' => 'CCTV', 'kind' => 'bool'],
                ['key' => 'boundaryWall', 'label' => 'Boundary Wall', 'kind' => 'bool'],
                ['key' => 'lawnGarden', 'label' => 'Lawn / Garden', 'kind' => 'bool'],
                ['key' => 'swimmingPool', 'label' => 'Swimming Pool', 'kind' => 'bool'],
            ],
        ],

        'building' => [
            'label' => 'Building & Unit',
            'fields' => [
                ['key' => 'buildingName', 'label' => 'Building Name', 'kind' => 'text', 'max' => 255],
                ['key' => 'towerBlock', 'label' => 'Tower / Block', 'kind' => 'text', 'max' => 100],
                ['key' => 'unitNumber', 'label' => 'Unit Number', 'kind' => 'text', 'max' => 50],
                ['key' => 'floorNumber', 'label' => 'Floor Number', 'kind' => 'int', 'min' => -10, 'max' => 200],
                ['key' => 'totalBuildingFloors', 'label' => 'Total Building Floors', 'kind' => 'int', 'max' => 200],
                ['key' => 'cornerUnit', 'label' => 'Corner Unit', 'kind' => 'bool'],
                ['key' => 'facing', 'label' => 'Facing', 'kind' => 'text', 'max' => 100],
            ],
        ],

        'buildingAmenities' => [
            'label' => 'Building Amenities',
            'fields' => [
                ['key' => 'elevator', 'label' => 'Elevator', 'kind' => 'bool'],
                ['key' => 'reception', 'label' => 'Reception', 'kind' => 'bool'],
                ['key' => 'lobby', 'label' => 'Lobby', 'kind' => 'bool'],
                ['key' => 'backupGenerator', 'label' => 'Backup Generator', 'kind' => 'bool'],
                ['key' => 'fireSafety', 'label' => 'Fire Safety', 'kind' => 'bool'],
                ['key' => 'gym', 'label' => 'Gym', 'kind' => 'bool'],
                ['key' => 'communityHall', 'label' => 'Community Hall', 'kind' => 'bool'],
                ['key' => 'kidsPlayArea', 'label' => 'Kids Play Area', 'kind' => 'bool'],
                ['key' => 'valetParking', 'label' => 'Valet Parking', 'kind' => 'bool'],
                ['key' => 'rooftop', 'label' => 'Rooftop', 'kind' => 'bool'],
            ],
        ],

        'views' => [
            'label' => 'View',
            'fields' => [
                [
                    'key' => 'view', 'label' => 'View', 'kind' => 'multiselect',
                    'options' => [
                        ['value' => 'city', 'label' => 'City View'],
                        ['value' => 'park', 'label' => 'Park View'],
                        ['value' => 'lake', 'label' => 'Lake View'],
                        ['value' => 'landmark', 'label' => 'Landmark View'],
                        ['value' => 'mosque', 'label' => 'Mosque View'],
                        ['value' => 'boulevard', 'label' => 'Main Boulevard View'],
                    ],
                ],
            ],
        ],

        'luxury' => [
            'label' => 'Luxury Features',
            'fields' => [
                ['key' => 'privateTerrace', 'label' => 'Private Terrace', 'kind' => 'bool'],
                ['key' => 'privatePool', 'label' => 'Private Pool', 'kind' => 'bool'],
                ['key' => 'privateElevator', 'label' => 'Private Elevator', 'kind' => 'bool'],
                ['key' => 'jacuzzi', 'label' => 'Jacuzzi', 'kind' => 'bool'],
                ['key' => 'servantRoom', 'label' => 'Servant Room', 'kind' => 'int', 'max' => 99],
            ],
        ],

        'duplex' => [
            'label' => 'Duplex Details',
            'fields' => [
                ['key' => 'lowerFloor', 'label' => 'Lower Floor', 'kind' => 'text', 'max' => 50],
                ['key' => 'upperFloor', 'label' => 'Upper Floor', 'kind' => 'text', 'max' => 50],
                ['key' => 'internalStaircase', 'label' => 'Internal Staircase', 'kind' => 'bool'],
            ],
        ],

        'portion' => [
            'label' => 'Portion Details',
            'fields' => [
                ['key' => 'portionFloor', 'label' => 'Portion Floor', 'kind' => 'text', 'max' => 50],
                ['key' => 'separateEntrance', 'label' => 'Separate Entrance', 'kind' => 'bool'],
                ['key' => 'separateElectricityMeter', 'label' => 'Separate Electricity Meter', 'kind' => 'bool'],
                ['key' => 'separateGasMeter', 'label' => 'Separate Gas Meter', 'kind' => 'bool'],
                ['key' => 'separateWaterConnection', 'label' => 'Separate Water Connection', 'kind' => 'bool'],
            ],
        ],

        'plot' => [
            'label' => 'Plot Details',
            'fields' => [
                ['key' => 'plotNumber', 'label' => 'Plot Number', 'kind' => 'text', 'max' => 50],
                ['key' => 'block', 'label' => 'Block', 'kind' => 'text', 'max' => 50],
                ['key' => 'street', 'label' => 'Street', 'kind' => 'text', 'max' => 100],
                ['key' => 'phase', 'label' => 'Phase', 'kind' => 'text', 'max' => 50],
                ['key' => 'sector', 'label' => 'Sector', 'kind' => 'text', 'max' => 50],
                ['key' => 'roadWidth', 'label' => 'Road Width (Ft)', 'kind' => 'decimal'],
                ['key' => 'frontWidth', 'label' => 'Front Width (Ft)', 'kind' => 'decimal'],
                ['key' => 'depth', 'label' => 'Depth (Ft)', 'kind' => 'decimal'],
                ['key' => 'cornerPlot', 'label' => 'Corner', 'kind' => 'bool'],
                ['key' => 'parkFacing', 'label' => 'Park Facing', 'kind' => 'bool'],
                ['key' => 'mainBoulevard', 'label' => 'Main Boulevard', 'kind' => 'bool'],
                ['key' => 'mainRoad', 'label' => 'Main Road', 'kind' => 'bool'],
            ],
        ],

        'plotStatus' => [
            'label' => 'Development & Possession',
            'fields' => [
                [
                    'key' => 'developmentStatus', 'label' => 'Development Status', 'kind' => 'select',
                    'options' => [
                        ['value' => 'developed', 'label' => 'Developed'],
                        ['value' => 'under_development', 'label' => 'Under Development'],
                        ['value' => 'non_developed', 'label' => 'Non-Developed'],
                    ],
                ],
                ['key' => 'possessionAvailable', 'label' => 'Possession Available', 'kind' => 'bool'],
                [
                    'key' => 'ballotStatus', 'label' => 'Balloting', 'kind' => 'select',
                    'options' => [
                        ['value' => 'balloted', 'label' => 'Balloted'],
                        ['value' => 'unballoted', 'label' => 'Unballoted'],
                    ],
                ],
            ],
        ],

        'agriculture' => [
            'label' => 'Agricultural Details',
            'fields' => [
                ['key' => 'irrigated', 'label' => 'Irrigated', 'kind' => 'bool'],
                ['key' => 'tubeWell', 'label' => 'Tube Well', 'kind' => 'bool'],
                ['key' => 'canalWater', 'label' => 'Canal Water', 'kind' => 'bool'],
                ['key' => 'roadAccess', 'label' => 'Road Access', 'kind' => 'bool'],
                ['key' => 'fruitPlants', 'label' => 'Fruit Plants', 'kind' => 'bool'],
                ['key' => 'crops', 'label' => 'Crops', 'kind' => 'text', 'max' => 255],
            ],
        ],

        'plotFile' => [
            'label' => 'File Details',
            'fields' => [
                ['key' => 'projectName', 'label' => 'Project Name', 'kind' => 'text', 'max' => 255],
                ['key' => 'developer', 'label' => 'Developer', 'kind' => 'text', 'max' => 255],
                ['key' => 'fileNumber', 'label' => 'File Number', 'kind' => 'text', 'max' => 100],
                ['key' => 'transferable', 'label' => 'Transferable', 'kind' => 'bool'],
                [
                    'key' => 'fileStatus', 'label' => 'File Status', 'kind' => 'select',
                    'options' => [
                        ['value' => 'allocation', 'label' => 'Allocation'],
                        ['value' => 'confirmed', 'label' => 'Confirmed'],
                        ['value' => 'balloted', 'label' => 'Balloted'],
                    ],
                ],
            ],
        ],

        'commercialUnit' => [
            'label' => 'Commercial Unit Details',
            'fields' => [
                ['key' => 'shopDepth', 'label' => 'Depth (Ft)', 'kind' => 'decimal'],
                ['key' => 'frontWidth', 'label' => 'Front Width (Ft)', 'kind' => 'decimal'],
                ['key' => 'mainRoadFacing', 'label' => 'Main Road / Mall Facing', 'kind' => 'bool'],
                ['key' => 'cornerShop', 'label' => 'Corner Unit', 'kind' => 'bool'],
                ['key' => 'brandOutlet', 'label' => 'Brand Outlet', 'kind' => 'bool'],
                ['key' => 'washroom', 'label' => 'Washroom', 'kind' => 'bool'],
                ['key' => 'attachedWashroom', 'label' => 'Attached Washroom', 'kind' => 'bool'],
                ['key' => 'escalator', 'label' => 'Escalator', 'kind' => 'bool'],
            ],
        ],

        'office' => [
            'label' => 'Office Details',
            'fields' => [
                ['key' => 'workspaces', 'label' => 'Rooms / Workspaces', 'kind' => 'int', 'max' => 999],
                ['key' => 'meetingRoom', 'label' => 'Meeting Room', 'kind' => 'int', 'max' => 99],
                ['key' => 'cabin', 'label' => 'Cabins', 'kind' => 'int', 'max' => 99],
                ['key' => 'pantryKitchen', 'label' => 'Pantry / Kitchen', 'kind' => 'bool'],
            ],
        ],

        'industrial' => [
            'label' => 'Industrial Details',
            'fields' => [
                ['key' => 'productionArea', 'label' => 'Production Area (Sq. Ft.)', 'kind' => 'decimal'],
                ['key' => 'officeArea', 'label' => 'Office Area (Sq. Ft.)', 'kind' => 'decimal'],
                ['key' => 'warehouseArea', 'label' => 'Warehouse Area (Sq. Ft.)', 'kind' => 'decimal'],
                ['key' => 'storageArea', 'label' => 'Storage Area (Sq. Ft.)', 'kind' => 'decimal'],
                ['key' => 'ceilingHeight', 'label' => 'Height (Ft)', 'kind' => 'decimal'],
                ['key' => 'loadingArea', 'label' => 'Loading Area', 'kind' => 'bool'],
                ['key' => 'loadingDocks', 'label' => 'Loading Docks', 'kind' => 'int', 'max' => 999],
                ['key' => 'electricityLoad', 'label' => 'Electricity Load (KVA)', 'kind' => 'decimal'],
                ['key' => 'crane', 'label' => 'Crane', 'kind' => 'bool'],
                ['key' => 'containerAccess', 'label' => 'Container Access', 'kind' => 'bool'],
                ['key' => 'industrialZone', 'label' => 'Industrial Zone', 'kind' => 'text', 'max' => 255],
            ],
        ],

        'plaza' => [
            'label' => 'Building / Plaza Details',
            'fields' => [
                ['key' => 'totalShops', 'label' => 'Shops', 'kind' => 'int', 'max' => 9999],
                ['key' => 'totalOffices', 'label' => 'Offices', 'kind' => 'int', 'max' => 9999],
                ['key' => 'totalApartments', 'label' => 'Apartments', 'kind' => 'int', 'max' => 9999],
                ['key' => 'rentalIncome', 'label' => 'Monthly Rental Income (PKR)', 'kind' => 'decimal'],
                ['key' => 'rentalYield', 'label' => 'ROI / Rental Yield (%)', 'kind' => 'decimal'],
            ],
        ],

        'room' => [
            'label' => 'Room Details',
            'fields' => [
                [
                    'key' => 'roomOccupancy', 'label' => 'Occupancy', 'kind' => 'select',
                    'options' => [
                        ['value' => 'private', 'label' => 'Private'],
                        ['value' => 'shared', 'label' => 'Shared'],
                    ],
                ],
                [
                    'key' => 'bathroomType', 'label' => 'Bathroom', 'kind' => 'select',
                    'options' => [
                        ['value' => 'attached', 'label' => 'Attached'],
                        ['value' => 'shared', 'label' => 'Shared'],
                    ],
                ],
                ['key' => 'kitchenAccess', 'label' => 'Kitchen Access', 'kind' => 'bool'],
                ['key' => 'bed', 'label' => 'Bed', 'kind' => 'bool'],
                ['key' => 'cupboard', 'label' => 'Cupboard', 'kind' => 'bool'],
                ['key' => 'wifi', 'label' => 'Wi-Fi', 'kind' => 'bool'],
                ['key' => 'utilitiesIncluded', 'label' => 'Utilities Included', 'kind' => 'bool'],
            ],
        ],

        'farmhouse' => [
            'label' => 'Farm House Details',
            'fields' => [
                ['key' => 'landArea', 'label' => 'Land Area (Kanal)', 'kind' => 'decimal'],
                ['key' => 'tubeWell', 'label' => 'Tube Well', 'kind' => 'bool'],
                ['key' => 'agriculturalLand', 'label' => 'Agricultural Land', 'kind' => 'bool'],
                ['key' => 'roadAccess', 'label' => 'Road Access', 'kind' => 'bool'],
            ],
        ],

        'project' => [
            'label' => 'Project Details',
            'fields' => [
                ['key' => 'projectName', 'label' => 'Project Name', 'kind' => 'text', 'max' => 255],
                ['key' => 'developer', 'label' => 'Developer', 'kind' => 'text', 'max' => 255],
            ],
        ],

        /*
         * Shown only when purpose = installment. The `purpose` key on a group
         * makes it conditional on the pricing mode rather than the type.
         */
        /*
         * Rendered by its own step in the form (PaymentPlanStep), which adds
         * live totals and a year-by-year schedule on top of these inputs.
         */
        'paymentPlan' => [
            'label' => 'Payment Plan',
            'purpose' => 'installment',
            'fields' => [
                ['key' => 'bookingAmount', 'label' => 'Booking Amount (PKR)', 'kind' => 'decimal'],
                ['key' => 'confirmationAmount', 'label' => 'Confirmation Amount (PKR)', 'kind' => 'decimal'],
                ['key' => 'downPayment', 'label' => 'Down Payment (PKR)', 'kind' => 'decimal'],
                ['key' => 'monthlyInstallment', 'label' => 'Monthly Installment (PKR)', 'kind' => 'decimal'],
                ['key' => 'monthlyCount', 'label' => 'Number of Monthly Instalments', 'kind' => 'int', 'max' => 360],
                ['key' => 'quarterlyInstallment', 'label' => 'Quarterly Instalment (PKR)', 'kind' => 'decimal'],
                ['key' => 'quarterlyCount', 'label' => 'Number of Quarterly Instalments', 'kind' => 'int', 'max' => 120],
                ['key' => 'biAnnualInstallment', 'label' => 'Bi-Annual Instalment (PKR)', 'kind' => 'decimal'],
                ['key' => 'biAnnualCount', 'label' => 'Number of Bi-Annual Instalments', 'kind' => 'int', 'max' => 60],
                ['key' => 'balloonPayment', 'label' => 'Balloon Payment (PKR)', 'kind' => 'decimal'],
                ['key' => 'balloonCount', 'label' => 'Number of Balloon Payments', 'kind' => 'int', 'max' => 20],
                ['key' => 'possessionPayment', 'label' => 'Possession Payment (PKR)', 'kind' => 'decimal'],
                ['key' => 'developmentCharges', 'label' => 'Development Charges (PKR)', 'kind' => 'decimal'],
                ['key' => 'otherCharges', 'label' => 'Other Charges (PKR)', 'kind' => 'decimal'],
                ['key' => 'planDurationYears', 'label' => 'Plan Duration (Years)', 'kind' => 'decimal', 'max' => 30],
            ],
        ],

        /* Shown only when purpose = rent. */
        'rental' => [
            'label' => 'Rental Terms',
            'purpose' => 'rent',
            'fields' => [
                ['key' => 'securityDeposit', 'label' => 'Security Deposit (PKR)', 'kind' => 'decimal'],
                ['key' => 'advanceMonths', 'label' => 'Advance (Months)', 'kind' => 'int', 'max' => 36],
                ['key' => 'maintenanceCharges', 'label' => 'Maintenance Charges (PKR)', 'kind' => 'decimal'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | property_type slug  =>  groups shown
    |--------------------------------------------------------------------------
    | `core` lists which built-in columns the form shows for this type, so a
    | plot never renders bedroom inputs.
    */

    'types' => [

        // ---------- Homes ----------
        'house' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'floors', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['rooms', 'structure', 'utilities', 'comfort'],
        ],
        'villa' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'floors', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['rooms', 'structure', 'utilities', 'comfort', 'luxury'],
        ],
        'townhouse' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'floors', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['rooms', 'structure', 'utilities', 'comfort'],
        ],
        'flat-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['rooms', 'building', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'penthouse' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['rooms', 'building', 'buildingAmenities', 'luxury', 'utilities', 'comfort', 'views'],
        ],
        'duplex' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['rooms', 'building', 'duplex', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'upper-portion' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing'],
            'groups' => ['portion', 'rooms', 'structure', 'utilities', 'comfort'],
        ],
        'lower-portion' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing'],
            'groups' => ['portion', 'rooms', 'structure', 'utilities', 'comfort'],
        ],
        'farm-house' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing'],
            'groups' => ['farmhouse', 'rooms', 'structure', 'utilities', 'comfort'],
        ],
        'room' => [
            'core' => ['furnishing'],
            'groups' => ['room', 'utilities'],
        ],

        // ---------- Plots ----------
        'residential-plot' => [
            'core' => [],
            'groups' => ['plot', 'plotStatus', 'utilities'],
        ],
        'commercial-plot' => [
            'core' => [],
            'groups' => ['plot', 'plotStatus', 'utilities'],
        ],
        'industrial-land' => [
            'core' => [],
            'groups' => ['plot', 'plotStatus', 'utilities'],
        ],
        'agricultural-land' => [
            'core' => [],
            'groups' => ['agriculture', 'plot'],
        ],
        'plot-file' => [
            'core' => [],
            'groups' => ['plotFile', 'plotStatus'],
        ],

        // ---------- Commercial ----------
        'shop' => [
            'core' => ['carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'office' => [
            'core' => ['carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['office', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'showroom' => [
            'core' => ['carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'factory' => [
            'core' => [],
            'groups' => ['industrial', 'structure', 'utilities', 'comfort'],
        ],
        'warehouse' => [
            'core' => [],
            'groups' => ['industrial', 'structure', 'utilities', 'comfort'],
        ],
        'building' => [
            'core' => ['carParking'],
            'groups' => ['plaza', 'structure', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'commercial-plaza' => [
            'core' => ['carParking'],
            'groups' => ['plaza', 'structure', 'buildingAmenities', 'utilities', 'comfort'],
        ],

        // ---------- High-Rise (residential) ----------
        'studio-apartment' => [
            'core' => ['baths', 'kitchens', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'one-bed-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'rooms', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'two-bed-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'rooms', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'three-bed-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'rooms', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'four-bed-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'rooms', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'luxury-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'rooms', 'luxury', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'high-rise-penthouse' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'rooms', 'luxury', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'duplex-penthouse' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'building', 'duplex', 'rooms', 'luxury', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'serviced-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'livingRooms', 'carParking', 'furnishing'],
            'groups' => ['project', 'building', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'hotel-apartment' => [
            'core' => ['beds', 'baths', 'kitchens', 'carParking', 'furnishing'],
            'groups' => ['project', 'building', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],

        // ---------- High-Rise (commercial) ----------
        'high-rise-shop' => [
            'core' => ['carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'high-rise-office' => [
            'core' => ['carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'office', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'high-rise-showroom' => [
            'core' => ['carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'corporate-office' => [
            'core' => ['carParking', 'furnishing', 'propertyCondition'],
            'groups' => ['project', 'office', 'building', 'buildingAmenities', 'utilities', 'comfort', 'views'],
        ],
        'food-court' => [
            'core' => ['carParking'],
            'groups' => ['project', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'restaurant' => [
            'core' => ['carParking', 'furnishing'],
            'groups' => ['project', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'commercial-floor' => [
            'core' => ['carParking'],
            'groups' => ['project', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
        'commercial-unit' => [
            'core' => ['carParking'],
            'groups' => ['project', 'commercialUnit', 'building', 'buildingAmenities', 'utilities', 'comfort'],
        ],
    ],
];
